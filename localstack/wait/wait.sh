#! /usr/bin/env bash

echo "Waiting for DynamoDB Tables"

iterations=0

while [ "$iterations" -lt 300 ]
do
  tables=$(awslocal dynamodb list-tables)

  if [[ $tables = *'"membrane-sessions"'* ]]
  then
    echo "Found all expected tables after $iterations seconds"
    exit 0
  fi

  ((iterations++))
  sleep 1
done

echo "Waited $iterations seconds for DynamoDBs Table before giving up"
echo "dynamodb list-tables results:"
echo "----------------------------------"
echo "$tables"
echo "----------------------------------"

exit 1
