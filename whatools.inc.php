<?php

class Whatools
{

  private $endpoint = 'https://api.wha.tools/v3';
  private $avatarSize = 640;

  public function __construct($key)
  {
    $this->key = $key;
  }

  private function _curl($method, $url, $query = null)
  {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    if (isset($query))
    {
      curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
    }
    $raw = curl_exec($ch);
    curl_close($ch);
    $res = json_decode($raw);
    if ($res)
    {
      if ($res->success)
        return isset($res->result) ? $res->result : true;
      else
        throw new Exception($res->error);
    }
    else
      return $raw;
  }

  private function get($action, $params = null)
  {
    $params["key"] = $this->key;
    $params["honor"] = true;
    $url = $this->endpoint . '/' . $action . '?' . http_build_query($params);
    return $this->_curl('GET', $url);
  }

  private function post($action, $params = null)
  {
    $params["key"] = $this->key;
    $params["honor"] = true;
    $url = $this->endpoint . '/' . $action;
    $query = http_build_query($params);
    return $this->_curl('POST', $url, $query);
  }

  public function login()
  {
    $res = $this->get('subscribe');
    if ($res)
      $this->whatsappInfo = $res;
    return $res;
  }

  public function logout()
  {
    $res = $this->get('unsubscribe');
    return $res;
  }

  public function messageGet($since = false, $until = false)
  {
    $params = array();
    if ($since)
      $params["since"] = $since;
    if ($until)
      $params["until"] = $until;
    $res = $this->get('message', $params);
    return $res;
  }

  public function messagePost($pn, $body)
  {
    return $this->post('message', array(
      'to' => $pn,
      'body' => $body
    ));
  }

  public function nicknameGet()
  {
    return $this->get('nickname');
  }

  public function nicknamePost($nickname = false)
  {
    $params = array();
    if ($nickname)
      $params["nickname"] = $nickname;
    return $this->post('nickname', $params);
  }

  public function statusGet()
  {
    return $this->get('status');
  }

  public function statusPost($status = false)
  {
    $params = array();
    if ($status)
      $params["status"] = $status;
    return $this->post('status', $params);
  }

  public function avatarGet($pn)
  {
    return $this->get('avatar', array(
      'pn' => $pn
    ));
  }

  public function avatarPost($path)
  {
    $type = pathinfo($path, PATHINFO_EXTENSION);

    switch($type)
    {
      case 'jpg':
      case 'jpeg':
        $img = imagecreatefromjpeg($path);
        break;
      case 'gif':
        $img = imagecreatefromgif($path);
        break;
      case 'png':
        $img = imagecreatefrompng($path);
        break;
      default:
        $img = false;
        break;
    }

    if (!$img)
      return array(
        'success' => false,
        'error' => 'picture-bad-format'
      );

    list($imgWidth, $imgHeight) = getimagesize($path);
    $prop = $imgWidth / $imgHeight;

    $x = $prop > 1 ? $this->avatarSize * $prop : $this->avatarSize;
    $y = $prop < 1 ? $this->avatarSize / $prop : $this->avatarSize;
    $xDelay = ($this->avatarSize - $x) / 2;
    $yDelay = ($this->avatarSize - $y) / 2;

    $thumb = imagecreatetruecolor($this->avatarSize, $this->avatarSize);
    imagefilledrectangle ($thumb, 0, 0, $this->avatarSize, $this->avatarSize, imagecolorallocate($thumb, 255, 255, 255));
    imagecopyresized($thumb, $img, $xDelay, $yDelay, 0, 0, $x, $y, $imgWidth, $imgHeight);

    ob_start();
    imagejpeg($thumb);
    $src = ob_get_contents();
    ob_end_clean();

    return $this->post('avatar', array(
      'src' => base64_encode($src)
    ));
  }

  public function picturePost($pn, $path, $caption = false)
  {
    $src = file_get_contents($path);
    $url = $this->endpoint . '/media/picture?key=' . $this->key . '&to=' . $pn;
    if ($caption)
      $url .= '&caption=' . urlencode($caption);
    $eol = "\r\n";
    $mime_boundary = md5(time());

    $data = '--' . $mime_boundary . $eol;
    $data .= 'Content-Disposition: form-data; name="attachment"; filename="' . end(explode("/", $path)) . '"' . $eol;
    $data .= 'Content-Type: ' . finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path) . $eol . $eol;
    $data .= base64_encode($src) . $eol;
    $data .= '--' . $mime_boundary . '--' . $eol . $eol;

    $params = array('http' => array(
                      'method' => 'POST',
                      'header' => 'Content-Type: multipart/form-data; boundary=' . $mime_boundary . $eol .'Content-Encoding: base64' . $eol,
                      'content' => $data
                   ));

    $ctx = stream_context_create($params);
    $raw = file_get_contents($url, FILE_TEXT, $ctx);
    if (explode(' ', $http_response_header[0])[1] == '404')
      throw new Exception('phone-number-not-in-whatsapp');
    else
    {
      $res = json_decode($raw);
      if ($res->success)
        return isset($res->result) ? $res->result : true;
      else
        throw new Exception($res->error);
    }
  }

}
