#!/bin/bash
set -e


#  SiJadwal — One-Command Installer
#  Usage:
#    curl -fsSL https://raw.githubusercontent.com/afiffaizin/sistem-penjadwalan/main/install.sh | bash
#    OR
#    ./install.sh  (from inside the repo)

REPO_URL="https://github.com/afiffaizin/sistem-penjadwalan.git"
PROJECT_DIR="sistem-penjadwalan"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' 

echo ""
echo -e "${CYAN}========================================${NC}"
echo -e "${CYAN}   SiJadwal — Automated Installer${NC}"
echo -e "${CYAN}========================================${NC}"
echo ""

# Check prerequisites
echo -e "${YELLOW}[1/5] Checking prerequisites...${NC}"

if ! command -v git &> /dev/null; then
    echo -e "${RED}ERROR: git is not installed.${NC}"
    echo "  Install git first:"
    echo "    Ubuntu/Debian : sudo apt install git"
    echo "    macOS         : brew install git"
    echo "    Windows       : https://git-scm.com/downloads"
    exit 1
fi
echo -e "  ${GREEN}✓ git found${NC}"

if ! command -v docker &> /dev/null; then
    echo -e "${RED}ERROR: docker is not installed.${NC}"
    echo "  Install Docker first:"
    echo "    All platforms : https://docs.docker.com/get-docker/"
    exit 1
fi
echo -e "  ${GREEN}✓ docker found${NC}"

# Check docker compose (v2 plugin or standalone)
if docker compose version &> /dev/null; then
    COMPOSE_CMD="docker compose"
elif command -v docker-compose &> /dev/null; then
    COMPOSE_CMD="docker-compose"
else
    echo -e "${RED}ERROR: docker compose is not available.${NC}"
    echo "  Install Docker Compose:"
    echo "    https://docs.docker.com/compose/install/"
    exit 1
fi
echo -e "  ${GREEN}✓ docker compose found${NC}"

# Check if Docker daemon is running
if ! docker info &> /dev/null 2>&1; then
    echo -e "${RED}ERROR: Docker daemon is not running.${NC}"
    echo "  Start Docker Desktop or run: sudo systemctl start docker"
    exit 1
fi
echo -e "  ${GREEN}✓ docker daemon running${NC}"
echo ""

# Clone repo if needed
echo -e "${YELLOW}[2/5] Preparing project...${NC}"

# Detect if we're inside the repo already
if [ -f "docker-compose.yml" ] && [ -d "sistem-penjadwalan-laravel" ] && [ -d "sistem-penjadwalan-python" ]; then
    echo -e "  ${GREEN}✓ Already inside the project directory${NC}"
else
    # Maybe we're called via curl from outside
    if [ -d "$PROJECT_DIR" ]; then
        echo "  Directory '$PROJECT_DIR' already exists. Pulling latest..."
        cd "$PROJECT_DIR"
        git pull origin main || git pull origin master || true
    else
        echo "  Cloning repository..."
        git clone "$REPO_URL" "$PROJECT_DIR"
        cd "$PROJECT_DIR"
    fi
    echo -e "  ${GREEN}✓ Repository ready${NC}"
fi
echo ""

# Setup .env files
echo -e "${YELLOW}[3/5] Setting up environment files...${NC}"

if [ ! -f "sistem-penjadwalan-laravel/.env" ]; then
    cp sistem-penjadwalan-laravel/.env.docker sistem-penjadwalan-laravel/.env
    echo -e "  ${GREEN}✓ Laravel .env created from .env.docker${NC}"
else
    echo -e "  ${CYAN}• Laravel .env already exists, skipping${NC}"
fi
echo ""

# Build & start containers
echo -e "${YELLOW}[4/5] Building and starting containers (this may take a few minutes)...${NC}"
echo ""

$COMPOSE_CMD up -d --build

echo ""

# Wait for healthchecks & show success
echo -e "${YELLOW}[5/5] Waiting for services to be ready...${NC}"

wait_for_container() {
    local container=$1
    local label=$2
    local max_wait=120
    local elapsed=0

    while [ $elapsed -lt $max_wait ]; do
        status=$(docker inspect --format='{{.State.Health.Status}}' "$container" 2>/dev/null || echo "not_found")

        if [ "$status" = "healthy" ]; then
            echo -e "  ${GREEN}✓ ${label} is healthy${NC}"
            return 0
        elif [ "$status" = "not_found" ]; then
            echo -e "  ${RED}✗ ${label} container not found${NC}"
            return 1
        fi

        sleep 3
        elapsed=$((elapsed + 3))
        echo -e "  ${CYAN}• Waiting for ${label}... (${elapsed}s)${NC}"
    done

    echo -e "  ${RED}✗ ${label} did not become healthy in ${max_wait}s${NC}"
    echo "  Check logs: docker logs ${container}"
    return 1
}

wait_for_container "sijadwal-db"      "Database"
wait_for_container "sijadwal-python"  "Python API"
wait_for_container "sijadwal-laravel" "Laravel App"

echo ""
echo -e "${GREEN}   SiJadwal — Successfully Running!${NC}"
echo ""
echo -e "  ${CYAN}Laravel App  :${NC} http://localhost:8000"
echo -e "  ${CYAN}Python API   :${NC} http://localhost:8080"
echo -e "  ${CYAN}Python Docs  :${NC} http://localhost:8080/docs"
echo -e "  ${CYAN}MySQL        :${NC} localhost:3307  (user: sijadwal / pass: sijadwal_secret)"
echo ""
echo -e "  ${YELLOW}Default Login:${NC}"
echo -e "    Username : sekjur"
echo -e "    Password : sekjur123"
echo ""
echo -e "  ${YELLOW}Commands:${NC}"
echo -e "    Stop     : $COMPOSE_CMD down"
echo -e "    Start    : $COMPOSE_CMD up -d"
echo -e "    Logs     : $COMPOSE_CMD logs -f"
echo -e "    Destroy  : $COMPOSE_CMD down -v  ${RED}(deletes database!)${NC}"
echo ""
