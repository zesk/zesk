<?php
namespace zesk\AWS;

/**
 */
use Aws\ElasticLoadBalancing\ElasticLoadBalancingClient as ElasticLoadBalancingClient;

/**
 *
 * @aliases aws-elb
 *
 * @author kent
 */
class Command_ELB extends Command {
	protected $option_types = array(
		"add" => "boolean",
		"remove" => "boolean"
	);
	function run() {
		$instance_id = $this->awareness->instance_id();
		$__ = array(
			"instance_id" => $instance_id
		);
		$this->log("Instance ID is {instance_id}", $__);
		
		$elbc = $this->application->factory(ElasticLoadBalancingClient::class);
		$load_balancers = $elbc->describeLoadBalancers();
		var_dump($load_balancers);
	}
}

/*
 * Obfuscated numbers below:
{
    "LoadBalancerDescriptions": [
        {
            "Subnets": [
                "subnet-60000a0c",
                "subnet-a00006fa",
                "subnet-b00008cf"
            ],
            "CanonicalHostedZoneNameID": "Z00000000000U",
            "CanonicalHostedZoneName": "productcodename-1234567890.us-west-2.elb.amazonaws.com",
            "ListenerDescriptions": [
                {
                    "Listener": {
                        "InstancePort": 80,
                        "LoadBalancerPort": 80,
                        "Protocol": "HTTP",
                        "InstanceProtocol": "HTTP"
                    },
                    "PolicyNames": [
                        "AWSConsole-LBCookieStickinessPolicy-productcodename-1234567890"
                    ]
                },
                {
                    "Listener": {
                        "InstancePort": 80,
                        "SSLCertificateId": "arn:aws:iam::1234567890:server-certificate/www.productcodename.com",
                        "LoadBalancerPort": 443,
                        "Protocol": "HTTPS",
                        "InstanceProtocol": "HTTP"
                    },
                    "PolicyNames": [
                        "AWSConsole-LBCookieStickinessPolicy-productcodename-1234567890",
                        "AWSConsole-SSLNegotiationPolicy-productcodename-1234567890"
                    ]
                }
            ],
            "HealthCheck": {
                "HealthyThreshold": 10,
                "Interval": 30,
                "Target": "HTTP:80/system-test/",
                "Timeout": 5,
                "UnhealthyThreshold": 2
            },
            "VPCId": "vpc-5000000e",
            "BackendServerDescriptions": [],
            "Instances": [
                {
                    "InstanceId": "i-70000001"
                },
                {
                    "InstanceId": "i-7000000b"
                }
            ],
            "DNSName": "productcodename-1234567890.us-west-2.elb.amazonaws.com",
            "SecurityGroups": [
                "sg-40000001"
            ],
            "Policies": {
                "LBCookieStickinessPolicies": [
                    {
                        "PolicyName": "AWSConsole-LBCookieStickinessPolicy-productcodename-0123456789012",
                        "CookieExpirationPeriod": 600
                    },
                    {
                        "PolicyName": "AWSConsole-LBCookieStickinessPolicy-productcodename-0123456789012",
                        "CookieExpirationPeriod": 600
                    }
                ],
                "AppCookieStickinessPolicies": [],
                "OtherPolicies": [
                    "ELBSecurityPolicy-2015-05",
                    "AWSConsole-SSLNegotiationPolicy-productcodename-0123456789012"
                ]
            },
            "LoadBalancerName": "productcodename",
            "CreatedTime": "2016-01-30T20:42:41.810Z",
            "AvailabilityZones": [
                "us-west-2a",
                "us-west-2b",
                "us-west-2c"
            ],
            "Scheme": "internet-facing",
            "SourceSecurityGroup": {
                "OwnerAlias": "9123456789013",
                "GroupName": "codename-lb-web"
            }
        },
        {
            "Subnets": [
                "subnet-60000a0c",
                "subnet-a00006fa",
                "subnet-b00008cf"
            ],
            "CanonicalHostedZoneNameID": "Z30000000000U",
            "CanonicalHostedZoneName": "companyname-012345678.us-west-2.elb.amazonaws.com",
            "ListenerDescriptions": [
                {
                    "Listener": {
                        "InstancePort": 80,
                        "LoadBalancerPort": 80,
                        "Protocol": "HTTP",
                        "InstanceProtocol": "HTTP"
                    },
                    "PolicyNames": [
                        "AWSConsole-LBCookieStickinessPolicy-companyname-0123456789012"
                    ]
                },
                {
                    "Listener": {
                        "InstancePort": 80,
                        "SSLCertificateId": "arn:aws:iam::0123456789012:server-certificate/companyname.com",
                        "LoadBalancerPort": 443,
                        "Protocol": "HTTPS",
                        "InstanceProtocol": "HTTP"
                    },
                    "PolicyNames": [
                        "AWSConsole-LBCookieStickinessPolicy-companyname-0123456789012",
                        "AWSConsole-SSLNegotiationPolicy-companyname-0123456789012"
                    ]
                }
            ],
            "HealthCheck": {
                "HealthyThreshold": 10,
                "Interval": 30,
                "Target": "HTTP:80/system-test/",
                "Timeout": 5,
                "UnhealthyThreshold": 2
            },
            "VPCId": "vpc-5012345e",
            "BackendServerDescriptions": [],
            "Instances": [
                {
                    "InstanceId": "i-70000001"
                },
                {
                    "InstanceId": "i-7000000b"
                }
            ],
            "DNSName": "companyname-012345678.us-west-2.elb.amazonaws.com",
            "SecurityGroups": [
                "sg-460dc821"
            ],
            "Policies": {
                "LBCookieStickinessPolicies": [
                    {
                        "PolicyName": "AWSConsole-LBCookieStickinessPolicy-companyname-0123456789012",
                        "CookieExpirationPeriod": 600
                    },
                    {
                        "PolicyName": "AWSConsole-LBCookieStickinessPolicy-companyname-0123456789012",
                        "CookieExpirationPeriod": 600
                    }
                ],
                "AppCookieStickinessPolicies": [],
                "OtherPolicies": [
                    "AWSConsole-SSLNegotiationPolicy-companyname-0123456789012",
                    "ELBSecurityPolicy-2015-05"
                ]
            },
            "LoadBalancerName": "companyname",
            "CreatedTime": "2016-02-05T19:00:34.810Z",
            "AvailabilityZones": [
                "us-west-2a",
                "us-west-2b",
                "us-west-2c"
            ],
            "Scheme": "internet-facing",
            "SourceSecurityGroup": {
                "OwnerAlias": "9123456789013",
                "GroupName": "codename-lb-web"
            }
        }
    ]
}
 */
