# Serverless PHP Session Handler

Provides a PHP session handler for AWS DynamoDB when hosting websites on Serverless.  

## Installation

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

```yaml
provider:
    iamRoleStatements:
    - Effect: Allow
      Action:
        - dynamodb:GetItem
        - dynamodb:PutItem
        - dynamodb:DeleteItem
        - dynamodb:DescribeTable
      Resource:
        - "Fn::GetAtt": [ sessionTable, Arn ]
```

In code, set up the handler.  Use the standard access methods for PHP sessions.

```php
    use WorldNewsGroup\Serverless\ServerlessSession;

    require('vendor/autoload.php');
    // put as close to the top (just under the require('vendor/autoload.php') is best) as possible
    ServerlessSession::getInstance('<dynamodb_table_name>', '<aws region>');

    ...
    // set the Session variable token to 'jwt'
    $_SESSION['token'] = 'jwt';

```