// src/composable/useErrorLogs.ts
import { computed, Ref } from 'vue';
import type { BankImport } from './useBankImports';

export function useErrorLogs(selectedImport: Ref<BankImport | null>) {
  
  const duplicateIds = computed<string[]>(() => {
    if (!selectedImport.value || !selectedImport.value.logs) return [];
    
    return selectedImport.value.logs
      .filter(log => log.error_message && log.error_message.toLowerCase().includes('duplicate'))
      .map(log => log.transaction_id)
      .filter((id): id is string => !!id) // Filtrowanie null/undefined dla TypeScriptu
      .filter((id, index, self) => self.indexOf(id) === index); // Usuwanie duplikatów jesli by sie pojawiły
  });

  return {
    duplicateIds
  };
}