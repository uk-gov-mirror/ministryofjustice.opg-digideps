locals {
  admin_sg_rules = merge(
    local.common_sg_rules_new,
    {
      pdf = {
        port        = 80
        type        = "egress"
        protocol    = "tcp"
        target_type = "security_group_id"
        target      = module.wkhtmltopdf_security_group.id
      },
      api = {
        port        = 443
        type        = "egress"
        protocol    = "tcp"
        target_type = "security_group_id"
        target      = module.api_rds_security_group.id
      }
      cache = {
        port        = 6379
        type        = "egress"
        protocol    = "tcp"
        target_type = "security_group_id"
        target      = module.admin_cache_security_group.id
      }
      admin_elb = {
        port        = 443
        type        = "ingress"
        protocol    = "tcp"
        target_type = "security_group_id"
        target      = module.admin_elb_security_group.id
      }
    }
  )
}

module "admin_service_security_group" {
  source = "./security_group"
  rules  = local.admin_sg_rules
  name   = aws_ecs_task_definition.admin.family
  tags   = local.default_tags
  vpc_id = data.aws_vpc.vpc.id
}

locals {
  admin_cache_sg_rules = {
    admin_service = {
      port        = 6379
      type        = "ingress"
      protocol    = "tcp"
      target_type = "security_group_id"
      target      = module.admin_service_security_group.id
    }
  }
}

module "admin_cache_security_group" {
  source = "./security_group"
  rules  = local.admin_cache_sg_rules
  name   = "admin-cache-${local.environment}"
  tags   = local.default_tags
  vpc_id = data.aws_vpc.vpc.id
}

locals {
  admin_elb_sg_rules = {
    admin_service = {
      port        = 443
      type        = "egress"
      protocol    = "tcp"
      target_type = "security_group_id"
      target      = module.admin_service_security_group.id
    }
  }
}

module "admin_elb_security_group" {
  source = "./security_group"
  rules  = local.admin_elb_sg_rules
  name   = "admin-elb-${local.environment}"
  tags   = local.default_tags
  vpc_id = data.aws_vpc.vpc.id
}

# Using a resource rather than module here due to a large list of IPs
resource "aws_security_group_rule" "admin_whitelist" {
  type              = "ingress"
  protocol          = "tcp"
  from_port         = 443
  to_port           = 443
  security_group_id = module.admin_elb_security_group.id
  cidr_blocks       = local.admin_whitelist
}