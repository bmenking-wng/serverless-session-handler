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
resources:
    Resources:
        sessionTable:
            Type: AWS::DynamoDB::Table
            Properties:
                #BillingMode: PAY_PER_REQUEST
                TableName: ${self:service}-sessions
                AttributeDefinitions:
                - AttributeName: id
                    AttributeType: S
                KeySchema:
                - AttributeName: id
                    KeyType: HASH
```

Verify the Serverless IAM roles or permissions are correct for connecting to that DynamoDB.

In code, set up the handler.  Use the standard access methods for PHP sessions.

```php
    use WorldNewsGroup\Serverless\ServerlessSession;

    ServerlessSession::getInstance('dynamodb_table_name', $_ENV['AWS_REGION']);

    ...

    $_SESSION['token'] = 'jwt';

```