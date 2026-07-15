<template>
  <BaseCard class="mt-8 border-t-2 border-l-0 border-r-0 border-b-0 pt-6 px-0 pb-0">
    <h2 class="text-xl font-bold mb-4 text-black uppercase">Error Logs & Validation</h2>
    
    <div v-if="selectedImport">
      <div class="mb-4 text-sm text-black border-b border-black pb-2">
        <p><strong>File:</strong> {{ selectedImport.file_name }}</p>
        <p><strong>Failed Records:</strong> {{ selectedImport.failed_records }}</p>
      </div>

      <div v-if="duplicateIds.length > 0" class="mb-6 border-2 border-red-600 p-4 bg-red-50">
        <h3 class="text-xs font-black text-red-600 uppercase mb-2"> REJECTED DUPLICATES IN FILE: </h3>
        <div class="flex flex-wrap gap-2">
          <span 
            v-for="id in duplicateIds" 
            :key="id" 
            class="px-2 py-0.5 bg-white border border-red-600 text-red-600 font-mono text-[10px] font-bold"
          >
            {{ id }}
          </span>
        </div>
      </div>

      <div v-if="selectedImport.logs && selectedImport.logs.length > 0" class="space-y-4 max-h-96 overflow-y-auto">
        <div 
          v-for="log in selectedImport.logs" 
          :key="log.id" 
          class="text-xs text-black border-b border-gray-100 pb-2"
        >
          <p class="font-bold text-red-600">Transaction ID: {{ log.transaction_id || 'N/A' }}</p>
          <p class="mt-1 text-black font-mono bg-white">{{ log.error_message }}</p>
        </div>
      </div>
      <p v-else-if="selectedImport.status === 'success'" class="text-sm text-green-600 font-bold animate-bounce">
        No errors found! All transactions imported successfully.
      </p>
      <p v-else-if="selectedImport.status === 'failed' && selectedImport.total_records === 0" class="text-sm text-yellow-600 font-bold animate-pulse">
        File is currently in queue. Waiting for processor to start...
      </p>
    </div>
    
    <div v-else class="text-black italic text-sm">
      Select an import from the list above to view detailed error logs.
    </div>
  </BaseCard>
</template>

<script setup lang="ts">
import { toRef } from 'vue';
import BaseCard from './ui/BaseCard.vue';
import type { BankImport } from '../composable/useBankImports';
import { useErrorLogs } from '../composable/useErrorLogs';

const props = defineProps<{
  selectedImport: BankImport | null;
}>();

// konwertowanie właściwości props na reaktywną referencję aby Composable mógł na bieżąco reagować na zmiany jakiegoś pliku
const selectedImportRef = toRef(props, 'selectedImport');

const { duplicateIds } = useErrorLogs(selectedImportRef);
</script>