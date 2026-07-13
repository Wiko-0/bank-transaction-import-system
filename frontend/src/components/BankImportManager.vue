<template>
  <div class="max-w-6xl mx-auto p-6 bg-white min-h-screen border border-black my-6 rounded-none">
    <h1 class="text-3xl font-black text-black mb-8 uppercase tracking-tight">Bank Transaction Import System</h1>

    <div class="bg-white p-6 border-2 border-black rounded-none mb-8">
      <h2 class="text-xl font-bold mb-4 text-black uppercase">Upload New File-br6</h2>
      <div class="flex items-center gap-4">
        <input 
          type="file" 
          ref="fileInput"
          accept=".csv,.json,.xml"
          @change="handleFileChange"
          class="block w-full text-sm text-black file:mr-4 file:py-2 file:px-4 file:rounded-none file:border file:border-black file:text-sm file:font-bold file:bg-white file:text-black hover:file:bg-black hover:file:text-white cursor-pointer"
        />
        <button 
          @click="uploadFile" 
          :disabled="!selectedFile || isUploading"
          class="px-5 py-2 bg-black text-white font-bold rounded-none hover:bg-gray-800 disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed transition-colors border border-black"
        >
          {{ isUploading ? 'IMPORTING...' : 'UPLOAD FILE' }}
        </button>
      </div>
      <p v-if="uploadMessage" class="mt-3 text-sm font-bold text-black border-l-4 border-black pl-2">{{ uploadMessage }}</p>
    </div>

    <div class="bg-white p-6 border-2 border-black rounded-none">
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
                <span :class="getStatusClass(importItem.status)" class="px-2 py-0.5 border border-black text-xs font-bold uppercase">
                  {{ importItem.status }}
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
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import ErrorLogsPanel from './ErrorLogsPanel.vue';

const API_URL = 'http://localhost:80/api';
const imports = ref([]);
const selectedImport = ref(null);
const selectedFile = ref(null);
const isUploading = ref(false);
const uploadMessage = ref('');
const fileInput = ref(null);

const fetchImports = async () => {
  try {
    const response = await axios.get(`${API_URL}/imports?_=${Date.now()}`);
    imports.value = response.data;
  } catch (error) {
    console.error("Error fetching history:", error);
  }
};

const fetchImportDetails = async (id) => {
  try {
    const response = await axios.get(`${API_URL}/imports/${id}?_=${Date.now()}`);
    selectedImport.value = response.data;
  } catch (error) {
    console.error("Error fetching details:", error);
  }
};

const handleFileChange = (event) => {
  selectedFile.value = event.target.files[0];
};

const uploadFile = async () => {
  if (!selectedFile.value) return;
  isUploading.value = true;
  uploadMessage.value = '';
  
  const formData = new FormData();
  formData.append('file', selectedFile.value);

  try {
    const response = await axios.post(`${API_URL}/imports`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    uploadMessage.value = "FILE IMPORTED.";
    selectedFile.value = null;
    if (fileInput.value) fileInput.value.value = ''; 
    await fetchImports(); 
    await fetchImportDetails(response.data.id); 
  } catch (error) {
    console.error("Error uploading file:", error);
    uploadMessage.value = "IMPORT ERROR.";
  } finally {
    isUploading.value = false;
  }
};

const getStatusClass = (status) => {
  switch (status) {
    case 'success': return 'bg-green-100 text-green-800';
    case 'partial': return 'bg-yellow-100 text-yellow-800';
    case 'failed': return 'bg-red-100 text-red-800';
    default: return 'bg-white text-black';
  }
};

onMounted(() => {
  fetchImports();
});
</script>