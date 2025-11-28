# Qwen Code - Guia de Uso

## ✅ Instalação Completa

- **NVM**: v0.40.0
- **Node.js**: v20.19.6 (LTS)
- **NPM**: v10.8.2
- **Qwen Code**: v0.3.0

## 🚀 Como Usar

### Opção 1: Script Rápido (Recomendado)
```bash
./qwen-start.sh
```

### Opção 2: Comando Direto
```bash
bash -c 'source ~/.bashrc && cd /home/jessessh/htdocs/srv1113343.hstgr.cloud && qwen'
```

### Opção 3: Adicionar ao PATH permanentemente
```bash
# Adicione ao final do seu ~/.bashrc
echo 'export NVM_DIR="$HOME/.nvm"' >> ~/.bashrc
echo '[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"' >> ~/.bashrc
source ~/.bashrc

# Depois é só rodar de qualquer lugar:
cd /home/jessessh/htdocs/srv1113343.hstgr.cloud
qwen
```

## 🔑 Autenticação

### Qwen OAuth (Recomendado - Grátis)
- **2.000 requisições/dia**
- **60 requisições/minuto**
- Autenticação automática via navegador

**Como usar:**
1. Execute `./qwen-start.sh`
2. O navegador abrirá automaticamente
3. Faça login na sua conta qwen.ai
4. Pronto! As credenciais são salvas automaticamente

### Alternativas Gratuitas

**Para China Continental:**
- **ModelScope**: 2.000 chamadas/dia grátis
  ```bash
  export OPENAI_API_KEY="sua_chave"
  export OPENAI_BASE_URL="https://api.modelscope.cn/api/v1"
  export OPENAI_MODEL="qwen-coder-plus"
  ```

**Internacional:**
- **OpenRouter**: 1.000 chamadas/dia grátis
  ```bash
  export OPENAI_API_KEY="sua_chave"
  export OPENAI_BASE_URL="https://openrouter.ai/api/v1"
  export OPENAI_MODEL="qwen/qwen-3-coder-plus"
  ```

## 💡 Comandos Úteis

### Dentro do Qwen Code:
```bash
> Explain this codebase structure          # Explicar estrutura
> Help me refactor this function          # Refatorar função
> Generate unit tests for this module     # Gerar testes
> /stats                                  # Ver uso de tokens
> /clear                                  # Limpar histórico
> /compress                               # Comprimir conversa
> /auth                                   # Trocar autenticação
> /help                                   # Ajuda completa
```

### Comandos CLI:
```bash
qwen --version                           # Versão
qwen --help                              # Ajuda
qwen --yolo                              # Modo automático (sem confirmações)
qwen --vlm-switch-mode once              # Usar visão uma vez
```

## ⚙️ Configuração

Arquivo de configuração: `~/.qwen/settings.json`

```json
{
  "sessionTokenLimit": 32000,
  "experimental": {
    "vlmSwitchMode": "once",
    "visionModelPreview": true
  }
}
```

## 📊 Recursos

- **Entendimento de Código**: Analisa codebases grandes
- **Edição Inteligente**: Refatoração e otimização
- **Automação**: Tarefas como PRs e rebases
- **Visão**: Análise de imagens automaticamente
- **Parser Otimizado**: Especialmente para Qwen-Coder

## 🔧 Troubleshooting

### Comando `qwen` não encontrado:
```bash
source ~/.bashrc
```

### Erro de autenticação:
```bash
qwen
# Siga o processo de login no navegador
```

### Resetar configuração:
```bash
rm -rf ~/.qwen
./qwen-start.sh
```

## 📚 Documentação Completa

- [Documentação oficial](https://qwenlm.github.io/qwen-code-docs/)
- [GitHub](https://github.com/QwenLM/qwen-code)
- [Qwen-Coder Models](https://github.com/QwenLM/Qwen3-Coder)

## 🎯 Exemplos de Uso

```bash
# Analisar o projeto Magento
qwen
> Explain the structure of app/code/GrupoAwamotos/

# Gerar testes
> Generate unit tests for app/code/GrupoAwamotos/Fitment/Model/FallbackSearch.php

# Refatorar código
> Help me optimize scripts/fallback_search_rebuild.php

# Revisar código
> Review the code quality of app/code/GrupoAwamotos/StoreSetup/

# Documentar
> Generate documentation for the custom modules
```
