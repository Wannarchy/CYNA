import axios from 'axios';

// ⚠️ Remplace par l'IP LAN de ta machine (ipconfig → IPv4)
export const API_BASE_URL = 'http://192.168.1.152/Cyna/api';

const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
  withCredentials: true,
  timeout: 10000,
});

apiClient.interceptors.response.use(
  (r) => r,
  (error) => Promise.reject(error)
);

export default apiClient;
