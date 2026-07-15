<template>
  <div class="max-w-6xl mx-auto p-6 bg-white min-h-screen border border-black my-6 rounded-none">
    <h1 class="text-3xl font-black text-black mb-8 uppercase tracking-tight">Bank Transaction Import System</h1>

    <BaseCard tag="section" class="mb-8">
      <h2 class="text-xl font-bold mb-4 text-black uppercase">Upload New File-br7</h2>
      <div class="flex items-center gap-4">
        <input 
          type="file" 
          ref="fileInput"
          accept=".csv,.json,.xml"
          @change="handleFileChange"
          class="block w-full text-sm text-black file:mr-4 file:py-2 file:px-4 file:rounded-none file:border file:border-black file:text-sm file:font-bold file:bg-white file:text-black hover:file:bg-black hover:file:text-white cursor-pointer"
        />
        <BaseButton 
          @click="uploadFile" 
          :disabled="!selectedFile || isUploading"
        >
          {{ isUploading ? 'UPLOADING...' : 'UPLOAD FILE' }}
        </BaseButton>
      </div>

      <p v-if="uploadMessage" class="mt-3 text-sm font-bold text-black border-l-4 border-black pl-2">
        {{ uploadMessage }}
      </p>
    </BaseCard>

    <BaseCard tag="section">
      <h2 class="text-xl font-bold mb-4 text-black uppercase">Import History</h2>
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse border border-black">
          <thead>
            <tr class="border-b-2 border-black bg-white text-black text-sm font-bold uppercase">
              <th class="p-3 border-r border-black">File Name</th>
              <th class="p-3 text-center border-r border-black">Records (Success / Failed / Total)</th>
              <th class="p-3 border-r border-black">Status</th>
              <th class="p-3 text-right">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="importItem in imports" :key="importItem.id" class="border-b border-black hover:bg-gray-50 text-sm">
              <td class="p-3 font-bold border-r border-black">{{ importItem.file_name }}</td>
              <td class="p-3 text-center border-r border-black font-mono">
                <span class="text-green-600 font-bold">{{ importItem.successful_records }}</span> / 
                <span class="text-red-600 font-bold">{{ importItem.failed_records }}</span> / 
                <span class="text-black">{{ importItem.total_records }}</span>
              </td>
              <td class="p-3 border-r border-black">
                <span :class="getStatusClass(importItem)" class="px-2 py-0.5 border border-black text-xs font-bold uppercase">
                  {{ getStatusText(importItem) }}
                </span>
              </td>
              <td class="p-3 text-right">
                <button 
                  @click="fetchImportDetails(importItem.id)" 
                  class="text-black font-bold underline hover:no-underline"
                >
                  [Details & Logs]
                </button>
              </td>
            </tr>
            <tr v-if="imports.length === 0">
              <td colspan="4" class="p-4 text-center text-gray-400 font-bold">No import history found.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <ErrorLogsPanel :selectedImport="selectedImport" />
    </BaseCard>
  </div>
</template>

<script setup lang="ts">
import ErrorLogsPanel from './ErrorLogsPanel.vue';
import BaseCard from './ui/BaseCard.vue';
import BaseButton from './ui/BaseButton.vue';
import { useBankImports } from '../composable/useBankImports';

// wszystkie potrzebne zmienne / metody z Composable
const {
  imports,
  selectedImport,
  selectedFile,
  isUploading,
  uploadMessage,
  fileInput,
  handleFileChange,
  uploadFile,
  fetchImportDetails,
  getStatusText,
  getStatusClass
} = useBankImports();
</script>