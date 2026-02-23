#!/bin/bash
# Fix di.xml semicolons and lowercase class names
FILE="app/code/GrupoAwamotos/ERPIntegration/etc/di.xml"

# Remove semicolons between attributes: "; xsi:type" => " xsi:type"
sed -i 's/"; xsi:type/" xsi:type/g' "$FILE"
sed -i 's/";xsi:type/" xsi:type/g' "$FILE"
sed -i 's/";xsi xsi:type/" xsi:type/g' "$FILE"
sed -i 's/";xsixsi xsi:type/" xsi:type/g' "$FILE"
sed -i 's/";xsixsixsi xsi:type/" xsi:type/g' "$FILE"

# Fix virtualtype tag casing: <virtualtype> => <virtualType>
sed -i 's/<virtualtype /<virtualType /g' "$FILE"
sed -i 's/<virtualtype>/<virtualType>/g' "$FILE"
sed -i 's/<\/virtualtype>/<\/virtualType>/g' "$FILE"

# Fix lowercase class references to proper casing
sed -i 's|grupoawamotos\\erpintegration\\logger\\virtuallogger|GrupoAwamotos\\ERPIntegration\\Logger\\VirtualLogger|g' "$FILE"
sed -i 's|grupoawamotos\\erpintegration\\logger\\virtualhandler|GrupoAwamotos\\ERPIntegration\\Logger\\VirtualHandler|g' "$FILE"
sed -i 's|grupoawamotos\\erpintegration\\model\\circuitbreaker|GrupoAwamotos\\ERPIntegration\\Model\\CircuitBreaker|g' "$FILE"
sed -i 's|grupoawamotos\\erpintegration\\model\\validator\\productvalidator|GrupoAwamotos\\ERPIntegration\\Model\\Validator\\ProductValidator|g' "$FILE"
sed -i 's|grupoawamotos\\erpintegration\\model\\validator\\customervalidator|GrupoAwamotos\\ERPIntegration\\Model\\Validator\\CustomerValidator|g' "$FILE"
sed -i 's|grupoawamotos\\erpintegration\\model\\validator\\stockvalidator|GrupoAwamotos\\ERPIntegration\\Model\\Validator\\StockValidator|g' "$FILE"
sed -i 's|magento\\directory\\model\\regionfactory|Magento\\Directory\\Model\\RegionFactory|g' "$FILE"
sed -i 's|grupoawamotos\\erpintegration\\model\\whatsapp\\zapiclient|GrupoAwamotos\\ERPIntegration\\Model\\WhatsApp\\ZApiClient|g' "$FILE"
sed -i 's|grupoawamotos\\erpintegration\\console\\command\\testconnectioncommand|GrupoAwamotos\\ERPIntegration\\Console\\Command\\TestConnectionCommand|g' "$FILE"
sed -i 's|grupoawamotos\\erpintegration\\console\\command\\syncproductscommand|GrupoAwamotos\\ERPIntegration\\Console\\Command\\SyncProductsCommand|g' "$FILE"
sed -i 's|grupoawamotos\\erpintegration\\console\\command\\syncstockcommand|GrupoAwamotos\\ERPIntegration\\Console\\Command\\SyncStockCommand|g' "$FILE"
sed -i 's|grupoawamotos\\erpintegration\\console\\command\\synccustomerscommand|GrupoAwamotos\\ERPIntegration\\Console\\Command\\SyncCustomersCommand|g' "$FILE"
sed -i 's|grupoawamotos\\erpintegration\\console\\command\\syncpricescommand|GrupoAwamotos\\ERPIntegration\\Console\\Command\\SyncPricesCommand|g' "$FILE"
sed -i 's|grupoawamotos\\erpintegration\\console\\command\\synclogscommand|GrupoAwamotos\\ERPIntegration\\Console\\Command\\SyncLogsCommand|g' "$FILE"
sed -i 's|grupoawamotos\\erpintegration\\console\\command\\syncimagescommand|GrupoAwamotos\\ERPIntegration\\Console\\Command\\SyncImagesCommand|g' "$FILE"
sed -i 's|grupoawamotos\\erpintegration\\console\\command\\circuitbreakercommand|GrupoAwamotos\\ERPIntegration\\Console\\Command\\CircuitBreakerCommand|g' "$FILE"
sed -i 's|grupoawamotos\\erpintegration\\console\\command\\erpstatuscommand|GrupoAwamotos\\ERPIntegration\\Console\\Command\\ErpStatusCommand|g' "$FILE"
sed -i 's|grupoawamotos\\erpintegration\\console\\command\\cleanlogscommand|GrupoAwamotos\\ERPIntegration\\Console\\Command\\CleanLogsCommand|g' "$FILE"
sed -i 's|grupoawamotos\\erpintegration\\console\\command\\diagnosecommand|GrupoAwamotos\\ERPIntegration\\Console\\Command\\DiagnoseCommand|g' "$FILE"
sed -i 's|grupoawamotos\\erpintegration\\console\\command\\syncorderscommand|GrupoAwamotos\\ERPIntegration\\Console\\Command\\SyncOrdersCommand|g' "$FILE"
sed -i 's|grupoawamotos\\erpintegration\\console\\command\\synccategoriescommand|GrupoAwamotos\\ERPIntegration\\Console\\Command\\SyncCategoriesCommand|g' "$FILE"
sed -i 's|grupoawamotos\\erpintegration\\console\\command\\whatsappstatuscommand|GrupoAwamotos\\ERPIntegration\\Console\\Command\\WhatsAppStatusCommand|g' "$FILE"

echo "All fixes applied to $FILE"
