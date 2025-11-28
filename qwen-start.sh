#!/bin/bash
# Script para iniciar Qwen Code com o ambiente correto

# Carregar NVM
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"

# Ir para o diretório do projeto
cd /home/jessessh/htdocs/srv1113343.hstgr.cloud

# Iniciar Qwen Code
qwen "$@"
