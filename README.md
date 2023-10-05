# Bref Serverless Session Handler

This option allows for PHP session data to be stored in an AWS DynamoDB table.

## Usage

Edit composer.json

```
...,
"repositories": [
    {
        "type": "vcs",
        "url": ""
    }
],
...
```

Run the require command for composer:

`composer require benmenking\session-store-bref`

In the serverless.yml:

```
resources:
  Resources:
    sessionTable:
      Type: AWS::DynamoDB::Table
      Properties:
        BillingMode: PAY_PER_REQUEST
        TableName: ${self:custom.stage}-${self:service}-sessions
        KeySchema:
          - AttributeName: id
            KeyType: HASH
        AttributeDefinitions:
          - AttributeName: id
            AttributeType: S
```