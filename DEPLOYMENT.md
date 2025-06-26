# Deployment Guide - Control Parental

Este documento describe cómo desplegar la aplicación Control Parental en AWS usando ECR, EC2 y GitHub Actions.

## Requisitos Previos

1. **Cuenta de AWS** con permisos para ECR, EC2 e IAM
2. **AWS CLI** instalado y configurado
3. **Docker** instalado
4. **Cuenta de GitHub** para CI/CD

## Paso 1: Configurar AWS

### 1.1 Instalar AWS CLI

```bash
# Windows (usando scoop)
scoop install aws

# o descarga desde: https://aws.amazon.com/cli/
```

### 1.2 Configurar credenciales de AWS

```bash
aws configure
```

Necesitarás:
- AWS Access Key ID
- AWS Secret Access Key
- Default region (ej: us-east-1)
- Default output format (json)

### 1.3 Crear ECR Repository

```bash
aws ecr create-repository --repository-name controlparental --region us-east-1
```

## Paso 2: Configurar EC2

### 2.1 Lanzar instancia EC2

1. Vai a AWS Console → EC2
2. Launch Instance
3. Selecciona **Amazon Linux 2** AMI
4. Tipo de instancia: **t3.medium** (mínimo recomendado)
5. Configura Security Group:
   - SSH (22) - Tu IP
   - HTTP (80) - 0.0.0.0/0
   - HTTPS (443) - 0.0.0.0/0
6. Crea o selecciona un Key Pair
7. Launch Instance

### 2.2 Instalar Docker en EC2

Conecta a la instancia vía SSH y ejecuta:

```bash
# Actualizar sistema
sudo yum update -y

# Instalar Docker
sudo yum install -y docker
sudo systemctl start docker
sudo systemctl enable docker
sudo usermod -a -G docker ec2-user

# Instalar Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Instalar AWS CLI
sudo yum install -y awscli

# Logout y login para aplicar cambios de grupo
exit
```

### 2.3 Configurar AWS CLI en EC2

```bash
aws configure
```

## Paso 3: Configurar GitHub Secrets

Ve a tu repositorio en GitHub → Settings → Secrets and Variables → Actions

Añade estos secrets:

```
AWS_ACCESS_KEY_ID=tu_access_key
AWS_SECRET_ACCESS_KEY=tu_secret_key
AWS_ACCOUNT_ID=tu_account_id
EC2_HOST=ip_publica_de_tu_ec2
EC2_USER=ec2-user
EC2_SSH_KEY=contenido_de_tu_private_key
DB_DATABASE=controlparental
DB_USERNAME=controlparental_user
DB_PASSWORD=tu_password_seguro
DB_ROOT_PASSWORD=tu_root_password_seguro
```

## Paso 4: Configurar Variables de Entorno

### 4.1 Crear .env.production

```bash
cp .env.example .env.production
```

Edita `.env.production` con la configuración de producción:

```env
APP_NAME=ControlParental
APP_ENV=production
APP_KEY=base64:generate_new_key
APP_DEBUG=false
APP_URL=http://tu-dominio.com

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=controlparental
DB_USERNAME=controlparental_user
DB_PASSWORD=password_seguro

# ... otros valores
```

## Paso 5: Despliegue Manual (Primera vez)

### 5.1 Build y Push a ECR

```bash
# Configurar variables
export AWS_ACCOUNT_ID=123456789012
export AWS_REGION=us-east-1

# Hacer script ejecutable
chmod +x deploy.sh

# Ejecutar despliegue
./deploy.sh
```

### 5.2 Deploy en EC2

```bash
# Configurar variables adicionales
export EC2_HOST=ip-de-tu-ec2
export EC2_USER=ec2-user

# Ejecutar despliegue completo
./deploy.sh latest
```

## Paso 6: Configurar CI/CD

El workflow de GitHub Actions se ejecutará automáticamente cuando:
- Hagas push a la rama `main`
- Crees un Pull Request

### Flujo del CI/CD:

1. **Test**: Ejecuta pruebas unitarias
2. **Build**: Construye la imagen Docker
3. **Push**: Sube la imagen a ECR
4. **Deploy**: Despliega en EC2

## Paso 7: Verificar Despliegue

1. Accede a `http://TU_IP_EC2`
2. Verifica que la aplicación carga correctamente
3. Revisa los logs si es necesario:

```bash
# En EC2
docker-compose -f docker-compose.aws.yml logs app
docker-compose -f docker-compose.aws.yml logs nginx
```

## Comandos Útiles

### Logs
```bash
# Ver logs de la aplicación
ssh ec2-user@TU_IP "docker-compose -f docker-compose.aws.yml logs app"

# Ver logs de nginx
ssh ec2-user@TU_IP "docker-compose -f docker-compose.aws.yml logs nginx"
```

### Actualizaciones
```bash
# Redesplegar con nueva imagen
ssh ec2-user@TU_IP "docker-compose -f docker-compose.aws.yml pull && docker-compose -f docker-compose.aws.yml up -d"
```

### Limpiar cache
```bash
ssh ec2-user@TU_IP "docker-compose -f docker-compose.aws.yml exec app php artisan cache:clear"
```

## Resolución de Problemas

### Error de permisos en storage
```bash
ssh ec2-user@TU_IP "docker-compose -f docker-compose.aws.yml exec app chown -R www-data:www-data /var/www/storage"
```

### Error de migración
```bash
ssh ec2-user@TU_IP "docker-compose -f docker-compose.aws.yml exec app php artisan migrate:status"
ssh ec2-user@TU_IP "docker-compose -f docker-compose.aws.yml exec app php artisan migrate --force"
```

### Verificar conectividad de base de datos
```bash
ssh ec2-user@TU_IP "docker-compose -f docker-compose.aws.yml exec app php artisan tinker --execute='DB::connection()->getPdo();'"
```

## Seguridad

- Cambia todas las contraseñas por defecto
- Configura SSL/TLS para HTTPS
- Restringe el acceso SSH a tu IP
- Usa AWS Systems Manager Session Manager cuando sea posible
- Mantén actualizadas las imágenes Docker

## Monitoreo

Considera configurar:
- CloudWatch para logs y métricas
- ALB (Application Load Balancer) para alta disponibilidad
- RDS para base de datos gestionada
- ElastiCache para cache Redis 