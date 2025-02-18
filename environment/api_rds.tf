module "api_aurora" {
  source                        = "./aurora"
  count                         = 1
  aurora_serverless             = local.account.aurora_serverless
  account_id                    = data.aws_caller_identity.current.account_id
  apply_immediately             = local.account.deletion_protection ? false : true
  cluster_identifier            = "api"
  db_subnet_group_name          = local.account.db_subnet_group
  deletion_protection           = local.account.deletion_protection ? true : false
  database_name                 = "api"
  engine_version                = local.account.psql_engine_version
  master_username               = "digidepsmaster"
  master_password               = data.aws_secretsmanager_secret_version.database_password.secret_string
  instance_count                = local.account.aurora_instance_count
  instance_class                = "db.t3.medium"
  kms_key_id                    = data.aws_kms_key.rds.arn
  replication_source_identifier = ""
  skip_final_snapshot           = local.account.deletion_protection ? false : true
  vpc_security_group_ids        = [module.api_rds_security_group.id]
  tags                          = local.default_tags
  log_group                     = aws_cloudwatch_log_group.api_cluster
}

locals {
  db = {
    endpoint = module.api_aurora[0].endpoint
    port     = module.api_aurora[0].port
    name     = module.api_aurora[0].name
    username = module.api_aurora[0].master_username
  }
}

data "aws_iam_role" "enhanced_monitoring" {
  name = "rds-enhanced-monitoring"
}

data "aws_kms_key" "rds" {
  key_id = "alias/aws/rds"
}

resource "aws_cloudwatch_log_group" "api_cluster" {
  name              = "/aws/rds/cluster/api-${local.environment}/postgresql"
  retention_in_days = 180
  tags              = local.default_tags
}

resource "aws_route53_record" "api_postgres" {
  name    = "postgres"
  type    = "CNAME"
  zone_id = aws_route53_zone.internal.id
  records = [local.db.endpoint]
  ttl     = 300
}

data "aws_caller_identity" "current" {}
