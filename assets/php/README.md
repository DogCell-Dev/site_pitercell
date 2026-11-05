# site_pitercell
Esse site terá a funcionalidade de automatizar orçamentos, disponibilizar status de reparos, comunicação com o cliente e no futuro uma loja online

## Configuração

1. Copie `.env.example` para `.env`.
2. Preencha `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` e `DB_PASS` com as credenciais do MySQL.
3. Informe o telefone internacional em `WHATSAPP_NUMBER`, somente números.
4. Importe `banco_piterCell.sql` no banco configurado.

O arquivo `.env` não deve ser versionado. Para criar uma senha de administrador, gere um hash com `password_hash('sua-senha', PASSWORD_DEFAULT)` e substitua o hash do administrador no banco.
