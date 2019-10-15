package main

import (
	"strconv"
	"os"
	"time"
	"fmt"
	"github.com/aws/aws-sdk-go/aws"
	"github.com/aws/aws-sdk-go/aws/session"
	"regexp"
	"github.com/aws/aws-sdk-go/aws/credentials/stscreds"
	"github.com/aws/aws-sdk-go/service/cloudwatchlogs"
	"github.com/aws/aws-sdk-go/service/ecs"
)

func main() {
	cluster := "ddpb2944"
	securityGroups := []string{"sg-0ee40a8bbc67747e3"}
	subnets := []string{"subnet-d0b880a6", "subnet-a31455fb", "subnet-9ad4d1fe"}
	taskDefinition := "sync-ddpb2944"
	command := []string{"./backup.sh"}
	containerName := "sync"
	logGroupName := "ddpb2944"
	streamPrefix := "sync"
	delay := 5
	timeOut := getEnvInt("DEPLOYER_TIMEOUT")

	// Credentials and config
	sess, _ := session.NewSession()
	creds := stscreds.NewCredentials(sess, "arn:aws:iam::248804316466:role/operator")

	awsConfig := aws.Config{Credentials: creds, Region: aws.String("eu-west-1")}

	// Service and tasks

	ecsSvc := ecs.New(sess, &awsConfig)
	tasksOutput := runTask(ecsSvc, cluster, securityGroups, subnets, taskDefinition, command, containerName)

	taskARN := tasksOutput.Tasks[0].TaskArn
	taskID := regexp.MustCompile("^.*/").ReplaceAllString(*taskARN, "")

	// Logging

	cloudwatchLogsSvc := cloudwatchlogs.New(sess, &awsConfig)

	cloudwatchLogsInput := &cloudwatchlogs.GetLogEventsInput{
		LogGroupName:  aws.String(logGroupName),
		LogStreamName: aws.String(fmt.Sprintf("%s/%s/%s", streamPrefix, containerName, taskID)),
		StartFromHead: aws.Bool(true),
	}

	count := 0

	describeTasksOutput := getTasks(ecsSvc, tasksOutput)

	for *describeTasksOutput.Tasks[0].LastStatus != "STOPPED" {
		describeTasksOutput = getTasks(ecsSvc, tasksOutput)

		cloudwatchlogsOutput, err := cloudwatchLogsSvc.GetLogEvents(cloudwatchLogsInput)

		cloudwatchLogsInput.NextToken = cloudwatchlogsOutput.NextForwardToken

		if err != nil {
			fmt.Println(err)
		}

		fmt.Println(cloudwatchlogsOutput.Events)

		if count * delay >= timeOut {
			fmt.Printf("Timed out after %v\n", timeOut)
			os.Exit(1)
		} 

		time.Sleep(time.Duration(delay) * time.Second)
		count++
	}

	fmt.Printf("Container exited with code %d", *describeTasksOutput.Tasks[0].Containers[0].ExitCode)

	os.Exit(int(*describeTasksOutput.Tasks[0].Containers[0].ExitCode))
}

func runTask(svc *ecs.ECS, cluster string, securityGroups []string, subnets []string, taskDefinition string, command []string, containerName string) *ecs.RunTaskOutput {
	taskInput := &ecs.RunTaskInput{
		Cluster:    aws.String(cluster),
		LaunchType: aws.String("FARGATE"),
		NetworkConfiguration: &ecs.NetworkConfiguration{
			AwsvpcConfiguration: &ecs.AwsVpcConfiguration{
				SecurityGroups: aws.StringSlice(securityGroups),
				Subnets:        aws.StringSlice(subnets),
			},
		},
		TaskDefinition: aws.String(taskDefinition),
		Overrides: &ecs.TaskOverride{
			ContainerOverrides: []*ecs.ContainerOverride{
				&ecs.ContainerOverride{
					Command: aws.StringSlice(command),
					Name:    aws.String(containerName),
				},
			},
		},
	}

	tasksOutput, err := svc.RunTask(taskInput)

	if err != nil {
		fmt.Println(err)
	}

	return tasksOutput
}

func getTasks(svc *ecs.ECS, tasksOutput *ecs.RunTaskOutput) *ecs.DescribeTasksOutput {
	describeTaskInput := &ecs.DescribeTasksInput{
		Cluster: tasksOutput.Tasks[0].ClusterArn,
		Tasks:   []*string{tasksOutput.Tasks[0].TaskArn},
	}

	describeTasksOutput, err := svc.DescribeTasks(describeTaskInput)

	if err != nil {
		fmt.Println(err)
	}

	return describeTasksOutput
}

func getEnvInt(name string) int {
	env, err := strconv.Atoi(os.Getenv(name))

	if err != nil {
		fmt.Printf("Error getting %s: %v", name, err)
	}

	return env
}
