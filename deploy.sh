#!/bin/bash

# Deploy script for Control Parental
set -e

# Configuration
AWS_REGION=${AWS_REGION:-us-east-1}
AWS_ACCOUNT_ID=${AWS_ACCOUNT_ID}
ECR_REPOSITORY=controlparental
IMAGE_TAG=${1:-latest}

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

echo_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

echo_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if AWS CLI is installed
if ! command -v aws &> /dev/null; then
    echo_error "AWS CLI is not installed. Please install it first."
    exit 1
fi

# Check if required environment variables are set
if [ -z "$AWS_ACCOUNT_ID" ]; then
    echo_error "AWS_ACCOUNT_ID environment variable is not set"
    exit 1
fi

# Login to ECR
echo_info "Logging in to Amazon ECR..."
aws ecr get-login-password --region $AWS_REGION | docker login --username AWS --password-stdin $AWS_ACCOUNT_ID.dkr.ecr.$AWS_REGION.amazonaws.com

# Build the Docker image
echo_info "Building Docker image..."
docker build -t $ECR_REPOSITORY:$IMAGE_TAG .

# Tag the image for ECR
echo_info "Tagging image for ECR..."
docker tag $ECR_REPOSITORY:$IMAGE_TAG $AWS_ACCOUNT_ID.dkr.ecr.$AWS_REGION.amazonaws.com/$ECR_REPOSITORY:$IMAGE_TAG

# Push the image to ECR
echo_info "Pushing image to ECR..."
docker push $AWS_ACCOUNT_ID.dkr.ecr.$AWS_REGION.amazonaws.com/$ECR_REPOSITORY:$IMAGE_TAG

echo_info "Docker image pushed successfully!"
echo_info "Image URI: $AWS_ACCOUNT_ID.dkr.ecr.$AWS_REGION.amazonaws.com/$ECR_REPOSITORY:$IMAGE_TAG"

# Deploy to EC2 (if EC2_HOST is provided)
if [ ! -z "$EC2_HOST" ]; then
    echo_info "Deploying to EC2 instance: $EC2_HOST"
    
    # Copy docker-compose file to EC2
    scp -o StrictHostKeyChecking=no docker-compose.aws.yml $EC2_USER@$EC2_HOST:~/
    scp -o StrictHostKeyChecking=no .env.production $EC2_USER@$EC2_HOST:~/
    
    # Deploy on EC2
    ssh -o StrictHostKeyChecking=no $EC2_USER@$EC2_HOST << EOF
        # Login to ECR on EC2
        aws ecr get-login-password --region $AWS_REGION | docker login --username AWS --password-stdin $AWS_ACCOUNT_ID.dkr.ecr.$AWS_REGION.amazonaws.com
        
        # Set environment variables
        export AWS_ACCOUNT_ID=$AWS_ACCOUNT_ID
        export AWS_REGION=$AWS_REGION
        export IMAGE_TAG=$IMAGE_TAG
        
        # Pull and deploy
        docker-compose -f docker-compose.aws.yml pull
        docker-compose -f docker-compose.aws.yml up -d
        
        # Run migrations
        docker-compose -f docker-compose.aws.yml exec -T app php artisan migrate --force
        
        # Clear caches
        docker-compose -f docker-compose.aws.yml exec -T app php artisan config:clear
        docker-compose -f docker-compose.aws.yml exec -T app php artisan view:clear
        docker-compose -f docker-compose.aws.yml exec -T app php artisan cache:clear
EOF
    
    echo_info "Deployment completed successfully!"
else
    echo_warn "EC2_HOST not provided. Skipping EC2 deployment."
    echo_info "To deploy to EC2, set EC2_HOST and EC2_USER environment variables and run again."
fi 