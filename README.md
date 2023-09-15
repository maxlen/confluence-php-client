# Confluence PHP Client

A Confluence RESTful API client in PHP

An Object Oriented wrapper for Confluence

## Requirements

* PHP >= 7.4.0

## Installation

add to file composer.json:

```bash
...
"repositories": [
  {
    "type": "git",
    "url": "https://github.com/maxlen/confluence-php-client.git"
  }
...
```

Run in console:

```bash

$ composer require maxlen/confluence-php-client
```

## Usage

### Authentication

#### Using Personal Access Tokens
```php
use Maxlen\ConfluenceClient\ConfluenceClient;

$client = new ConfluenceClient('https://url-to-conluence');

//authenticate with a private access token
//@see https://confluence.atlassian.com/enterprise/using-personal-access-tokens-1026032365.html
$client->authenticate('NjU2OTA4NDI2MTY5OkBznOUO8YjaUF7KoOruZRXhILJ9');
```
#### Using BaseAuth
```php
$client = new ConfluenceClient('https://USERNAME:PASSWORD@url-to-conluence');
```
or
```php
use Maxlen\ConfluenceClient\ConfluenceClient;

$client = new ConfluenceClient('https://url-to-conluence');
$client->authenticateBasicAuth('USERNAME', 'PASSWORD');
```

### Fetch pages, comments and attachments

#### Find pages by title and space key
```php
/* @var $client Maxlen\ConfluenceClient\ConfluenceClient */


//Get the page we created
$searchResults = $client->content()->find([
    'spaceKey' => 'testSpaceKey',
    'title' => 'Test'
]);

//first page
$createdPage = $searchResults->getResultAt(0);
```

#### Fetch a page or comment by content id
```php
/* @var $client Maxlen\ConfluenceClient\ConfluenceClient */

//Get a page or comment
$resultContent = $client->content()->get(1234567890);
```

#### Fetch page descendants
```php
use Maxlen\ConfluenceClient\Api\Content;
/* @var $client Maxlen\ConfluenceClient\ConfluenceClient */
/* @var $page Maxlen\ConfluenceClient\Entity\ContentPage */

//get child content
$childContent = $client->content()->children($page, Content::CONTENT_TYPE_PAGE); //\Maxlen\ConfluenceClient\Entity\ContentSearchResult
```

### Manipulating  content

#### Create new page
```php
use Maxlen\ConfluenceClient\Entity\ContentPage;
/* @var $client Maxlen\ConfluenceClient\ConfluenceClient */

//Create a confluence content page
$page = new ContentPage();

//Configure your page
$page->setSpace('testSpaceKey')
    ->setTitle('Test')
    ->setContent('<p>test page</p>');

//Create the page in confluence in the test space
$client->content()->create($page);
```

#### Create new comment
```php
/* @var $client Maxlen\ConfluenceClient\ConfluenceClient */

//get a page by id
$page = $client->content()->get(123456789);

//attach a comment to the page
$comment = $page->createComment('my comment text');

//save the comment
$client->content()->create($comment);
```

#### Create subpage
```php
/* @var $client Maxlen\ConfluenceClient\ConfluenceClient */

//get a page by id
$page = $client->content()->get(123456789);

//attach a subpage to page
$subPage = $page->createSubpage('subpage title', 'subpage content');

//save the page
$client->content()->create($subPage);
```

#### Update content
```php
/* @var $client Maxlen\ConfluenceClient\ConfluenceClient */

//get content by id
$page = $client->content()->get(123456789);

//change content
$page->setContent('new content')
    ->setTitle('new title');

//save the changes
$client->content()->update($page);
```

#### Delete content
```php
/* @var $client Maxlen\ConfluenceClient\ConfluenceClient */

//get content by id
$page = $client->content()->get(123456789);

//delete content
$client->content()->delete($page);
```

