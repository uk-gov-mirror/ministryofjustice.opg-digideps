variable "environment" {
  description = "Name of the environment to create secrets for."
}

variable "secrets" {
  description = "List of secrets to create for the environment."
  type        = set(string)
}

variable "tags" {
  description = "Tags to apply to secrets."
}
