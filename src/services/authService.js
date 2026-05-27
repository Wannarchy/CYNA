import apiClient from '../utils/apiClient';

export const login = async ({ email, mot_de_passe }) => {
  const res = await apiClient.post('/auth/login.php', { email, mot_de_passe });
  return res.data;
};
export const logout = async () => { await apiClient.post('/auth/logout.php'); };
export const register = async (data) => { const res = await apiClient.post('/auth/register.php', data); return res.data; };
export const getMe = async () => { const res = await apiClient.get('/auth/me.php'); return res.data; };
export const forgotPassword = async ({ email }) => { const res = await apiClient.post('/auth/forgot_password.php', { email }); return res.data; };
export const resendConfirmation = async ({ email }) => { const res = await apiClient.post('/auth/resend_confirmation.php', { email }); return res.data; };
