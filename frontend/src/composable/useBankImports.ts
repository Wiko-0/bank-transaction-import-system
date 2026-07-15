// src/composables/useBankImports.ts
import { ref, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';

export interface ImportLog {
  id: number;
  transaction_id: string | null;
  error_message: string;
}

export interface BankImport {
  id: number;
  file_name: string;
  successful_records: number;
  failed_records: number;
  total_records: number;
  status: 'success' | 'partial' | 'failed';
  logs?: ImportLog[];
}

const API_URL = 'http://localhost:80/api';

export function useBankImports() {
  const imports = ref<BankImport[]>([]);
  const selectedImport = ref<BankImport | null>(null);
  const selectedFile = ref<File | null>(null);
  const isUploading = ref(false);
  const uploadMessage = ref('');
  const fileInput = ref<HTMLInputElement | null>(null);
  let pollingInterval: ReturnType<typeof setInterval> | null = null;

  const fetchImports = async (): Promise<void> => {
    try {
      const response = await axios.get<BankImport[]>(`${API_URL}/imports?_=${Date.now()}`);
      imports.value = response.data;
      
      if (selectedImport.value) {
        const updatedDetails = imports.value.find(item => item.id === selectedImport.value?.id);
        if (updatedDetails) {
          selectedImport.value = updatedDetails;
        }
      }
      
      checkPollingRequirements();
    } catch (error) {
      console.error("Error fetching history:", error);
    }
  };

  const fetchImportDetails = async (id: number): Promise<void> => {
    try {
      const response = await axios.get<BankImport>(`${API_URL}/imports/${id}?_=${Date.now()}`);
      selectedImport.value = response.data;
    } catch (error) {
      console.error("Error fetching details:", error);
    }
  };

  const handleFileChange = (event: Event): void => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
      selectedFile.value = target.files[0];
    }
  };

  const uploadFile = async (): Promise<void> => {
    if (!selectedFile.value) return;
    isUploading.value = true;
    uploadMessage.value = '';
    
    const formData = new FormData();
    formData.append('file', selectedFile.value);

    try {
      const response = await axios.post<{ id: number }>(`${API_URL}/imports`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      
      uploadMessage.value = "FILE RECEIVED AND QUEUED.";
      selectedFile.value = null;
      if (fileInput.value) fileInput.value.value = ''; 
      
      await fetchImports(); 
      await fetchImportDetails(response.data.id);
      
      startPolling(); 
    } catch (error) {
      console.error("Error uploading file:", error);
      uploadMessage.value = "IMPORT ERROR.";
    } finally {
      isUploading.value = false;
    }
  };

  const getStatusText = (item: BankImport): string => {
    if (item.status === 'failed' && item.total_records === 0) {
      return 'IN QUEUE';
    }
    return item.status;
  };

  const getStatusClass = (item: BankImport): string => {
    if (item.status === 'failed' && item.total_records === 0) {
      return 'bg-blue-100 text-blue-800 animate-pulse border-blue-400'; 
    }
    switch (item.status) {
      case 'success': return 'bg-green-100 text-green-800';
      case 'partial': return 'bg-yellow-100 text-yellow-800';
      case 'failed': return 'bg-red-100 text-red-800';
      default: return 'bg-white text-black';
    }
  };

  const startPolling = (): void => {
    if (pollingInterval) return;
    pollingInterval = setInterval(async () => {
      await fetchImports();
    }, 2000); 
  };

  const stopPolling = (): void => {
    if (pollingInterval) {
      clearInterval(pollingInterval);
      pollingInterval = null;
    }
  };

  const checkPollingRequirements = (): void => {
    const hasPendingJobs = imports.value.some(item => item.status === 'failed' && item.total_records === 0);
    if (hasPendingJobs) {
      startPolling();
    } else {
      stopPolling();
    }
  };

  onMounted(() => {
    fetchImports();
  });

  onBeforeUnmount(() => {
    stopPolling();
  });

  return {
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
  };
}