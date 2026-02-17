---
applyTo: "**/Test/**/*.php,**/tests/**/*.php"
---

# Regras para Testes (Magento 2 / PHPUnit)

## Framework
- PHPUnit (via `vendor/bin/phpunit`)
- Magento Integration Tests (`dev/tests/integration/`)
- Magento Unit Tests (`dev/tests/unit/`)

## Estrutura do Arquivo de Teste
```php
<?php
declare(strict_types=1);

namespace GrupoAwamotos\ModuleName\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ServiceNameTest extends TestCase
{
    private ServiceName $subject;
    private DependencyInterface&MockObject $dependencyMock;

    protected function setUp(): void
    {
        $this->dependencyMock = $this->createMock(DependencyInterface::class);
        $this->subject = new ServiceName($this->dependencyMock);
    }

    public function testMethodReturnsExpectedResult(): void
    {
        // Arrange
        $this->dependencyMock->method('getData')->willReturn('value');
        // Act
        $result = $this->subject->method();
        // Assert
        $this->assertEquals('expected', $result);
    }
}
```

## Padrões
- Testar COMPORTAMENTO, não implementação
- Nome: `testDescricaoDoComportamento` ou `test_descricao_do_comportamento`
- Um assert por teste (preferencialmente)
- Mock apenas dependências externas (via `createMock`)
- Testar happy path E error cases
- Testar edge cases (null, array vazio, string vazia)
- Usar `@dataProvider` para múltiplos cenários

## Para Integration Tests
- Estender `\Magento\TestFramework\TestCase\AbstractController`
- Usar `@magentoDbIsolation enabled` para isolamento
- Usar `@magentoConfigFixture` para configurações

## NUNCA
- Testes que dependem de ordem de execução
- Testes sem assertions reais
- ObjectManager em unit tests (use mocks)
- Testes que dependem de dados do banco de produção
