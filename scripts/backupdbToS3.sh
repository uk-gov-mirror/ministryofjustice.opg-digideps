#!/bin/bash

#$1=bucket_name
#$2=pipeline

usage ()
{
  echo "Usage : $0  <S3 bucket name> <snapshot name>"
  exit
}

if [ "$#" -ne 2 ]
then
  usage
fi

if (PGPASSWORD="$API_DATABASE_PASSWORD" pg_dump -C -h $API_DATABASE_HOSTNAME -U $API_DATABASE_USERNAME $API_DATABASE_NAME | gzip | aws s3 cp --region eu-west-1 --sse aws:kms - s3://$1/$2)
then
        echo "DB snapshot created copied to S3 bucket location s3://$1/$2 SUCCESSFULLY"
else
        echo "DB snapshot s3://$1/$2 creation or upload to S3 backup bucket FAILED"
	usage
fi
