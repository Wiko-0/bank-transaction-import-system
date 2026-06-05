<template>
  <div class="mt-8 border-t-2 border-black pt-6 bg-white">
    <h2 class="text-xl font-bold mb-4 text-black uppercase">Error Logs</h2>
    
    <div v-if="selectedImport">
      <div class="mb-4 text-sm text-black border-b border-black pb-2">
        <p><strong>File:</strong> {{ selectedImport.file_name }}</p>
        <p><strong>Failed Records:</strong> {{ selectedImport.failed_records }}</p>
      </div>

      <div v-if="selectedImport.logs && selectedImport.logs.length > 0" class="space-y-4 max-h-96 overflow-y-auto">
        <div 
          v-for="log in selectedImport.logs" 
          :key="log.id" 
          class="text-xs text-black"
        >
          <p class="font-bold text-red-600">Transaction ID: {{ log.transaction_id || 'N/A' }}</p>
          <p class="mt-1 text-black font-mono bg-white">{{ log.error_message }}</p>
        </div>
      </div>
      <p v-else class="text-sm text-green-600 font-bold">
        No errors found! All transactions imported successfully.
      </p>
    </div>
    
    <div v-else class="text-black italic text-sm">
      Select an import from the list above to view detailed error logs.
    </div>
  </div>
</template>

<script setup>
defineProps({
  selectedImport: Object
});
</script>