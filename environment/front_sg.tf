locals {
  front_sg_rules = {
    ecr     = local.common_sg_rules.ecr
    logs    = local.common_sg_rules.logs
    s3      = local.common_sg_rules.s3
    ssm     = local.common_sg_rules.ssm
    ecr_api = local.common_sg_rules.ecr_api
    secrets = local.common_sg_rules.secrets
    cache = {
      port        = 6379
      type        = "egress"
      protocol    = "tcp"
      target_type = "security_group_id"
      target      = module.frontend_cache_security_group.id
    }
    api = {
      port        = 443
      type        = "egress"
      protocol    = "tcp"
      target_type = "security_group_id"
      target      = module.api_service_security_group.id
    }
    pdf = {
      port        = 80
      type        = "egress"
      protocol    = "tcp"
      target_type = "security_group_id"
      target      = module.wkhtmltopdf_security_group.id
    }
    scan_integration = {
      port        = 8080
      type        = "egress"
      protocol    = "tcp"
      target_type = "security_group_id"
      target      = module.scan_security_group.id
    }
    mock_sirius_integration = {
      port        = 8080
      type        = "egress"
      protocol    = "tcp"
      target_type = "security_group_id"
      target      = module.mock_sirius_integration_security_group.id
    }
    front_elb = {
      port        = 443
      type        = "ingress"
      protocol    = "tcp"
      target_type = "security_group_id"
      target      = module.front_elb_security_group.id
    }
    notify = {
      port        = 443
      type        = "egress"
      protocol    = "tcp"
      target_type = "cidr_block"
      target      = "0.0.0.0/0"
    }
  }
}

module "front_service_security_group" {
  source = "./security_group"
  rules  = local.front_sg_rules
  name   = "front-service"
  tags   = local.default_tags
  vpc_id = data.aws_vpc.vpc.id
}

locals {
  front_elb_sg_rules = {
    front_service = {
      port        = 443
      type        = "egress"
      protocol    = "tcp"
      target_type = "security_group_id"
      target      = module.front_service_security_group.id
    }
  }
}

module "front_elb_security_group" {
  source = "./security_group"
  rules  = local.front_elb_sg_rules
  name   = "front-alb"
  tags   = local.default_tags
  vpc_id = data.aws_vpc.vpc.id
}

# Using resources rather than a module here due to a large list of IPs

resource "aws_security_group_rule" "front_elb_http_in" {
  type              = "ingress"
  protocol          = "tcp"
  from_port         = 80
  to_port           = 80
  security_group_id = module.front_elb_security_group.id
  cidr_blocks       = local.front_allow_list
}

resource "aws_security_group_rule" "front_elb_https_in" {
  type              = "ingress"
  protocol          = "tcp"
  from_port         = 443
  to_port           = 443
  security_group_id = module.front_elb_security_group.id
  cidr_blocks       = local.front_allow_list
}

//No room for rules left in front_elb_security_group
module "front_elb_security_group_route53_hc" {
  source = "./security_group"
  rules  = local.front_elb_sg_rules
  name   = "front-alb"
  tags   = local.default_tags
  vpc_id = data.aws_vpc.vpc.id
}

resource "aws_security_group_rule" "front_elb_route53_hc_in" {
  type              = "ingress"
  protocol          = "tcp"
  from_port         = 443
  to_port           = 443
  security_group_id = module.front_elb_security_group_route53_hc.id
  cidr_blocks       = local.route53_healthchecker_ips
}
