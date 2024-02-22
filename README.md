# Serverless PHP Session Handler

## Intent


## Installation

Add the following repository to your composer.json:

```
...
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/bmenking-wng/serverless-session-handler"
    },
    ...
],
...
```

Installation is easy via [Composer](https://getcomposer.org/):

```bash

$ composer require worldnewsgroup/serverless-session-handler

```

or add it manually to your composer.json.

## Usage

Create a DynamoDB table with the following defintion (serverless resource definition):

```yaml
Resources:
    resources:
        sessionTable:
            Type: AWS::DynamoDB::Table
            Properties:
            TableName: ${tablename}
            KeySchema:
                - AttributeName: jwt
                KeyType: HASH
            AttributeDefinitions:
                - AttributeName: jwt
                AttributeType: S
```

Verify the Serverless IAM roles or permissions are correct for connecting to that DynamoDB.

In code, set up the handler.  Use the standard access methods for PHP sessions.

```php
    use WorldNewsGroup\Serverless\ServerlessSession;

    $handler = ServerlessSession('dynamodb_table_name', $_ENV['AWS_REGION']);

    ...

    $_SESSION['token'] = 'jwt';

```