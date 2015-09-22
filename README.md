whatools-php
=========
PHP lib for integrating Whatools into your app easily

# Notice
This lib works only with v3 API. Please make sure your Whatools line is configured to use such API version.

# API reference
## Setting up
The only thing you need is including this library and then create an API object by passing your Whatools API key as a parameter to the class constructor.
```php
include("whatools.inc.php");
$w = new Whatools("Put here your API key");
```
Remember that you can get the API key for your Whatools line by logging into Whatools and then going to ```Advanced settings > REST API```.

## Logging in and out
Logging in and out is the analog process in v3 API to subscribing and unsubscribing in older API versions. Nevertheless, in v3, when you log out you are effectively closing the connection between WhatsApp servers and your account, so you can be sure that you never miss a single message.
```php
$w->login();
echo "Logged in as +", $w->whatsappInfo->cc, $w->whatsappInfo->pn, "\n";
$w->logout();
```

## Setting your nickname
```php
$w->nicknamePost("John Doe");
```
## Getting your nickname
```php
$nickname = $w->nicknameGet("John Doe");
```
## Setting your status message
```php
$w->statusPost("To be, or not to be, that is the question.");
```
## Getting your status message
```php
$status = $w->statusGet();
```
## Setting your avatar
```php
$w->avatarPost("Route for an image file");
```
## Getting and storing someone's avatar
```php
$avatar = $w->avatarGet("Phone number in international format");
file_put_contents("avatar.jpg", $avatar);
```
## Sending a message
```php
$w->messagePost("Phone number in international format", "Body of the message");
```
## Sending a picture
```php
$w->picturePost("Phone number in international format", "Route for an image file", "Optional caption");
```
## Retrieving and printing messages received and sent since last logout
```php
$messages = $w->messageGet();
foreach ($messages as $message)
{
  if ($msg->mine)
    echo "> ", $msg->to;
  else
    echo "< ", $msg->from;
  echo "\n\t\"", $msg->body, "\"";
  echo "\n\t@", $msg->stamp;
  echo "\n\tACK: ", $msg->ack, "\n";
}
```
