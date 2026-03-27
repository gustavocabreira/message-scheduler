import axios from "axios";

export type ValidationErrors = {
  message?: string;
  [key: string]: string[] | string | undefined;
};
export type ApiResponse<T> = { ok: boolean; data: T | null; errors: ValidationErrors | null; status?: number };

const client = axios.create({
  baseURL: import.meta.env.VITE_API_URL + "/api",
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
  withCredentials: true,
  withXSRFToken: true,
});

client.interceptors.response.use(
  (response: any) => {
    return response;
  },
  async (err: any) => {
    return Promise.reject(err);
  }
);

export const http = {
  async request<T>(method: "GET" | "POST" | "PUT" | "PATCH" | "DELETE", url: string, payload?: any): Promise<ApiResponse<T>> {
    try {
      const { data, status } = await client.request({ method, url, data: payload });

      return {
        ok: true,
        data: data.data,
        errors: null,
        status,
      };
    } catch (error: any) {
      const errors = error?.response?.data ?? {
        message: error.message ?? "Unknown error",
        errors: {},
      };

      return { ok: false, data: null, errors, status: error?.status };
    }
  },
};

export const getCsrfCookie = async () => {
  await axios.get(import.meta.env.VITE_API_URL + "/sanctum/csrf-cookie", {
    withCredentials: true,
    withXSRFToken: true,
  });
}

