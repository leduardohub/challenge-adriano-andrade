# DigitalHub_RuleByDevice — Integration & E2E Tests

Este documento descreve os testes de integração e end-to-end adicionados para o módulo `DigitalHub_RuleByDevice`, como executá-los (local e via docker-magento wrapper), fixtures criadas, mapeamento para critérios de aceite e instruções de troubleshooting.

Resumo do que foi implementado

- Testes de integração unitária e plugin (localizados em `dev/tests/integration/testsuite/DigitalHub/RuleByDevice/Plugin` e `Model`):
  - `Plugin/Quote/SetDeviceFromHeaderTest.php` — valida comportamento do plugin que popula `device_type` no `Quote` a partir do header `X-Device-Type`.
  - `Model/Rule/Condition/DeviceTest.php` — valida `Device` condition (true/false/empty).

- Testes end-to-end (E2E) criados em `dev/tests/integration/testsuite/DigitalHub/RuleByDevice/EndToEnd`:
  - `RuleDeviceE2eTest.php` — cria produto + regra (fixture) e valida desconto quando `device_type = 'android'`.
  - `RuleDeviceE2eMultiValueTest.php` — mesma ideia mas com condição multi-valor ('android,ios') e quote com `device_type = 'ios'`.
  - `RuleDeviceE2eNoDeviceTest.php` — valida que a regra NÃO é aplicada quando o quote não tem `device_type`.

- Fixtures adicionadas em `dev/tests/integration/testsuite/DigitalHub/RuleByDevice/_files`:
  - `create_product_and_rule.php` — cria produto e SalesRule com condição `device_type == android` e persiste SKU em fixture storage.
  - `create_product_and_rule_multivalue.php` — cria produto e SalesRule com condição `device_type == android,ios`.

Objetivos / critérios de aceite cobertos

1) O plugin `SetDeviceFromHeader` deve popular o `device_type` do `Quote` quando o header `X-Device-Type` estiver presente e válido (web/android/ios). — coberto por `SetDeviceFromHeaderTest`.

2) A condição `Device` deve validar corretamente quando o `Quote` contém `device_type` e respeitar valores múltiplos. — coberto por `DeviceTest.php` (unit/integration) e `RuleDeviceE2eMultiValueTest.php` (E2E).

3) Fluxo completo de SalesRule: criar regra que use a condição Device e validar que o desconto é aplicado a um quote com `device_type` correspondendo à condição. — coberto por `RuleDeviceE2eTest.php` (positivo), `RuleDeviceE2eMultiValueTest.php` (positivo multi) e `RuleDeviceE2eNoDeviceTest.php` (negativo).

Instruções detalhadas para execução

Requisitos rápidos
- Este repositório está configurado para rodar testes de integração dentro do ambiente Docker `markshust/docker-magento` (recomendado). Para executar os testes no host diretamente, você precisa ter PHP CLI com as extensões necessárias (pdo_mysql, etc.) e os serviços (MySQL, OpenSearch, RabbitMQ) acessíveis no host.

Nota: o ambiente Magento usado para desenvolver e rodar estes testes foi montado usando o projeto `markshust/docker-magento` (https://github.com/markshust/docker-magento). As instruções e comandos de wrapper/compose deste documento pressupõem essa stack Docker.

A. Rodando via wrapper `bin/dev-test-run` (docker-magento)

Observação importante: os testes agora possuem um `phpunit.xml.dist` localizado no módulo (`app/code/DigitalHub/RuleByDevice/Test/Integration/phpunit.xml.dist`). Esse arquivo contém o bootstrap que aponta para o framework de integração do Magento, portanto você pode executar os testes do módulo diretamente via wrapper apontando para esse arquivo de configuração.

1) Rodar um arquivo de teste específico (via wrapper)

- A partir da raiz do projeto `magento` (onde `bin/dev-test-run` está), execute:

```
bin/dev-test-run integration -c ../../../app/code/DigitalHub/RuleByDevice/Test/Integration/phpunit.xml.dist testsuite/DigitalHub/RuleByDevice/EndToEnd/RuleDeviceE2eTest.php
```

- Você também pode rodar todos os testes do módulo via wrapper apontando para o phpunit do módulo:

```
bin/dev-test-run integration -c ../../../app/code/DigitalHub/RuleByDevice/Test/Integration/phpunit.xml.dist
```

B. Rodando diretamente do arquivo `phpunit.xml.dist` do módulo (modo recomendado para CI local ao versionar testes no módulo)

- Execute este comando a partir da raiz do projeto `magento` (recomendado, pois usa o phpunit instalado via composer no `src/vendor`):

```bash
php -d memory_limit=-1 src/vendor/bin/phpunit -c src/app/code/DigitalHub/RuleByDevice/Test/Integration/phpunit.xml.dist
```

- Alternativamente, você pode entrar no container PHP e rodar o phpunit usando o arquivo do módulo (útil para debug):

```bash
bin/docker-compose exec -T phpfpm bash -lc "cd dev/tests/integration && ../../../vendor/bin/phpunit -c ../../../app/code/DigitalHub/RuleByDevice/Test/Integration/phpunit.xml.dist"
```

C. Observações sobre paths e bootstrap
- O `phpunit.xml.dist` do módulo contém um `bootstrap` que referencia o framework de integração do Magento. Para que o bootstrap funcione, execute o phpunit no contexto do projeto (não em uma cópia isolada) — por isso a recomendação de rodar a partir da raiz do projeto ou via wrapper/container.
- Como os testes agora carregam fixtures que vivem dentro do módulo (`app/code/DigitalHub/RuleByDevice/Test/Integration/_files`), não é necessário copiar fixtures para `dev/tests/integration/_files` — o teste faz `require BP . '/app/code/DigitalHub/RuleByDevice/Test/Integration/_files/...'` internamente.

Arquivos criados (resumo)

- Tests (agora mantidos dentro do módulo em `app/code/DigitalHub/RuleByDevice/Test/Integration`):
  - app/code/DigitalHub/RuleByDevice/Test/Integration/Plugin/Quote/SetDeviceFromHeaderTest.php
  - app/code/DigitalHub/RuleByDevice/Test/Integration/Model/Rule/Condition/DeviceTest.php
  - app/code/DigitalHub/RuleByDevice/Test/Integration/EndToEnd/RuleDeviceE2eTest.php
  - app/code/DigitalHub/RuleByDevice/Test/Integration/EndToEnd/RuleDeviceE2eMultiValueTest.php
  - app/code/DigitalHub/RuleByDevice/Test/Integration/EndToEnd/RuleDeviceE2eNoDeviceTest.php

- Fixtures (dentro do módulo):
  - app/code/DigitalHub/RuleByDevice/Test/Integration/_files/create_product_and_rule.php
  - app/code/DigitalHub/RuleByDevice/Test/Integration/_files/create_product_and_rule_multivalue.php

Troubleshooting & dicas

1) Sem saída ao executar o wrapper
- O wrapper usa `bin/docker-compose exec -T phpfpm` para rodar o comando dentro do container sem pseudo-tty; se não houver saída, verifique containers:

```
/bin/docker-compose ps
```

- Confirme que o serviço `phpfpm` (ou `php`) está UP.

2) Erros de resolução de host (ex.: `getaddrinfo for db failed`)
- Rode os testes dentro do container (o DNS interno do Docker resolve `db`, `rabbitmq`, `opensearch`).
- Se rodando no host, altere `dev/tests/integration/etc/install-config-mysql.php.dist` para usar `127.0.0.1` ou host apropriado.

3) Erros de constantes PDO não definidas (ex.: PDO::MYSQL_ATTR_SSL_KEY)
- Garanta que a extensão `pdo_mysql` esteja habilitada na CLI do PHP dentro do container (normalmente já está). Se rodando no host, instale/ative `phpX.Y-mysql` para sua versão.

4) AMQP / RabbitMQ
- Se o ambiente não tem RabbitMQ e o instalador tentar conectar, defina `amqp-host` como `null` em `dev/tests/integration/etc/install-config-mysql.php.dist` para que o instalador não inclua parâmetros AMQP.
- Para rodar com RabbitMQ, adicione um serviço `rabbitmq` ao `docker-compose` (ex.: `image: rabbitmq:3-management`) e mantenha `amqp-host` apontando para `rabbitmq`.
