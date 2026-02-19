import { renderHook, waitFor, act } from '@testing-library/react';
import { createTestQueryClient } from '../helpers/renderWithProviders';
import { QueryClientProvider } from '@tanstack/react-query';
import { createElement, type ReactNode } from 'react';
import {
    useProviders,
    useTestConnection,
    useDeleteProvider,
    useHuggyRedirect,
    useHuggyCallback,
    PROVIDERS_QUERY_KEY,
} from '@/hooks/useProviders';
import type { ProviderConnection } from '@/types/provider';

jest.mock('@/lib/api', () => ({
    __esModule: true,
    default: {
        get: jest.fn(),
        post: jest.fn(),
        delete: jest.fn(),
    },
}));

import api from '@/lib/api';
const mockedApi = api as jest.Mocked<typeof api>;

const mockProvider: ProviderConnection = {
    id: 1,
    provider_type: 'huggy',
    provider_label: 'Huggy',
    status: 'active',
    settings: null,
    connected_at: '2026-02-18T21:00:00Z',
    last_synced_at: null,
    created_at: '2026-02-18T21:00:00Z',
};

function createWrapper() {
    const queryClient = createTestQueryClient();
    return {
        queryClient,
        wrapper: ({ children }: { children: ReactNode }) =>
            createElement(QueryClientProvider, { client: queryClient }, children),
    };
}

beforeEach(() => {
    jest.clearAllMocks();
});

describe('useProviders()', () => {
    it('fetches providers list from GET /providers', async () => {
        mockedApi.get.mockResolvedValueOnce({ data: { data: [mockProvider] } });
        const { wrapper } = createWrapper();
        const { result } = renderHook(() => useProviders(), { wrapper });

        await waitFor(() => expect(result.current.isSuccess).toBe(true));

        expect(mockedApi.get).toHaveBeenCalledWith('/providers');
        expect(result.current.data?.data).toEqual([mockProvider]);
    });

    it('returns empty array when no providers exist', async () => {
        mockedApi.get.mockResolvedValueOnce({ data: { data: [] } });
        const { wrapper } = createWrapper();
        const { result } = renderHook(() => useProviders(), { wrapper });

        await waitFor(() => expect(result.current.isSuccess).toBe(true));
        expect(result.current.data?.data).toEqual([]);
    });

    it('exposes isError true when API call fails', async () => {
        mockedApi.get.mockRejectedValueOnce(new Error('Network error'));
        const { wrapper } = createWrapper();
        const { result } = renderHook(() => useProviders(), { wrapper });

        await waitFor(() => expect(result.current.isError).toBe(true));
    });
});

describe('useTestConnection()', () => {
    it('calls POST /providers/{id}/test-connection', async () => {
        mockedApi.post.mockResolvedValueOnce({
            data: { connected: true, provider: mockProvider },
        });
        const { wrapper } = createWrapper();
        const { result } = renderHook(() => useTestConnection(), { wrapper });

        act(() => {
            result.current.mutate(1);
        });

        await waitFor(() => expect(result.current.isSuccess).toBe(true));

        expect(mockedApi.post).toHaveBeenCalledWith('/providers/1/test-connection');
        expect(result.current.data?.connected).toBe(true);
    });

    it('returns connected: false when connection fails', async () => {
        mockedApi.post.mockResolvedValueOnce({
            data: { connected: false, provider: { ...mockProvider, status: 'error' } },
        });
        const { wrapper } = createWrapper();
        const { result } = renderHook(() => useTestConnection(), { wrapper });

        act(() => { result.current.mutate(1); });

        await waitFor(() => expect(result.current.isSuccess).toBe(true));
        expect(result.current.data?.connected).toBe(false);
    });
});

describe('useDeleteProvider()', () => {
    it('calls DELETE /providers/{id}', async () => {
        mockedApi.delete.mockResolvedValueOnce({ data: { message: 'Deleted.' } });
        const { wrapper } = createWrapper();
        const { result } = renderHook(() => useDeleteProvider(), { wrapper });

        act(() => { result.current.mutate(1); });

        await waitFor(() => expect(result.current.isSuccess).toBe(true));
        expect(mockedApi.delete).toHaveBeenCalledWith('/providers/1');
    });

    it('exposes isError true when deletion fails', async () => {
        mockedApi.delete.mockRejectedValueOnce(new Error('Forbidden'));
        const { wrapper } = createWrapper();
        const { result } = renderHook(() => useDeleteProvider(), { wrapper });

        act(() => { result.current.mutate(1); });

        await waitFor(() => expect(result.current.isError).toBe(true));
    });
});

describe('useHuggyRedirect()', () => {
    it('calls GET /auth/huggy/redirect and returns authorization_url', async () => {
        const url = 'https://huggy.io/oauth/authorize?client_id=xxx';
        mockedApi.get.mockResolvedValueOnce({ data: { authorization_url: url } });
        const { wrapper } = createWrapper();
        const { result } = renderHook(() => useHuggyRedirect(), { wrapper });

        act(() => { result.current.mutate(); });

        await waitFor(() => expect(result.current.isSuccess).toBe(true));
        expect(mockedApi.get).toHaveBeenCalledWith('/auth/huggy/redirect');
        expect(result.current.data?.authorization_url).toBe(url);
    });
});

describe('useHuggyCallback()', () => {
    it('calls GET /auth/huggy/callback with code param', async () => {
        mockedApi.get.mockResolvedValueOnce({
            data: { message: 'Huggy account connected successfully.' },
        });
        const { wrapper, queryClient } = createWrapper();
        queryClient.setQueryData(PROVIDERS_QUERY_KEY, { data: [] });

        const { result } = renderHook(() => useHuggyCallback(), { wrapper });

        act(() => { result.current.mutate('auth_code_abc'); });

        await waitFor(() => expect(result.current.isSuccess).toBe(true));
        expect(mockedApi.get).toHaveBeenCalledWith('/auth/huggy/callback', {
            params: { code: 'auth_code_abc' },
        });
    });

    it('invalidates providers query on success', async () => {
        mockedApi.get.mockResolvedValueOnce({
            data: { message: 'Huggy account connected successfully.' },
        });
        mockedApi.get.mockResolvedValueOnce({ data: { data: [mockProvider] } });

        const { wrapper, queryClient } = createWrapper();
        const invalidateSpy = jest.spyOn(queryClient, 'invalidateQueries');

        const { result } = renderHook(() => useHuggyCallback(), { wrapper });

        act(() => { result.current.mutate('auth_code_abc'); });

        await waitFor(() => expect(result.current.isSuccess).toBe(true));
        expect(invalidateSpy).toHaveBeenCalledWith(
            expect.objectContaining({ queryKey: PROVIDERS_QUERY_KEY }),
        );
    });
});
