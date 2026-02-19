import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/api';
import type {
    ProvidersListResponse,
    TestConnectionResponse,
    HuggyRedirectResponse,
} from '@/types/provider';

export const PROVIDERS_QUERY_KEY = ['providers'] as const;

export function useProviders() {
    return useQuery<ProvidersListResponse>({
        queryKey: PROVIDERS_QUERY_KEY,
        queryFn: () => api.get<ProvidersListResponse>('/providers').then((r) => r.data),
    });
}

export function useTestConnection() {
    const queryClient = useQueryClient();

    return useMutation<TestConnectionResponse, Error, number>({
        mutationFn: (providerId: number) =>
            api
                .post<TestConnectionResponse>(`/providers/${providerId}/test-connection`)
                .then((r) => r.data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: PROVIDERS_QUERY_KEY });
        },
    });
}

export function useDeleteProvider() {
    const queryClient = useQueryClient();

    return useMutation<void, Error, number>({
        mutationFn: (providerId: number) =>
            api.delete(`/providers/${providerId}`).then(() => undefined),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: PROVIDERS_QUERY_KEY });
        },
    });
}

export function useHuggyRedirect() {
    return useMutation<HuggyRedirectResponse, Error>({
        mutationFn: () =>
            api.get<HuggyRedirectResponse>('/auth/huggy/redirect').then((r) => r.data),
    });
}

export function useHuggyCallback() {
    const queryClient = useQueryClient();

    return useMutation<{ message: string }, Error, string>({
        mutationFn: (code: string) =>
            api
                .get<{ message: string }>(`/auth/huggy/callback`, { params: { code } })
                .then((r) => r.data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: PROVIDERS_QUERY_KEY });
        },
    });
}
