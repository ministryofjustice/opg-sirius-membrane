#! /usr/bin/env bash
awslocal dynamodb create-table \
    --table-name membrane-sessions \
    --attribute-definitions AttributeName=id,AttributeType=S \
    --key-schema AttributeName=id,KeyType=HASH \
    --provisioned-throughput ReadCapacityUnits=1000,WriteCapacityUnits=1000
