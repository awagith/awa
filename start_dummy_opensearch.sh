#!/bin/bash
# Inicia o servidor dummy do OpenSearch em background
nohup php -S 127.0.0.1:9200 -t dummy-opensearch/ > var/log/dummy_opensearch.log 2>&1 &
echo "Dummy OpenSearch iniciado na porta 9200 (PID: $!)"
