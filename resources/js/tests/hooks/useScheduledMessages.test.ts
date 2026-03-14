import { renderHook, waitFor, act } from '@testing-library/react';
import { createTestQueryClient } from '../helpers/renderWithProviders';
import { QueryClientProvider } from '@tanstack/react-query';
import { createElement, type ReactNode } from 'react';
import {
    useScheduledMessages,
    useCreateScheduledMessage,
    useCancelScheduledMessage,
    useMessageLogs,
    SCHEDULED_MESSAGES_QUERY_KEY,
} from '@/hooks/useScheduledMessages';
import type { ScheduledMessage, MessageLog } from '@/types/scheduled-message';

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

const mockMessage: ScheduledMessage = {
    id: 1,
    contact_id: 'contact-123',
    contact_name: 'Alice Smith',
    message: 'Hello, Alice!',
    scheduled_at: '2026-03-01T10:00:00Z',
    status: 'pending',
    attempts: 0,
    provider_connection_id: 1,
    created_at: '2026-02-18T21:00:00Z',
    updated_at: '2026-02-18T21:00:00Z',
    provider_connection: {
        id: 1,
        provider_type: 'huggy',
        provider_label: 'Huggy',
    },
};

const mockLog: MessageLog = {
    id: 1,
    attempt: 1,
    status: 'failed',
    response: null,
    error_message: 'Connection timeout',
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

describe('useScheduledMessages()', () => {
    it('fetches scheduled messages from GET /scheduled-messages', async () => {
        mockedApi.get.mockResolvedValueOnce({
            data: {
                data: [mockMessage],
                meta: { current_page: 1, last_page: 1, per_page: 20, total: 1 },
                links: { first: null, last: null, prev: null, next: null },
            },
        });

        const { wrapper } = createWrapper();
        const { result } = renderHook(() => useScheduledMessages(), { wrapper });

        await waitFor(() => expect(result.current.isSuccess).toBe(true));

        expect(mockedApi.get).toHaveBeenCalledWith('/scheduled-messages', { params: undefined });
        expect(result.current.data?.data).toEqual([mockMessage]);
    });

    it('passes filters as query params', async () => {
        mockedApi.get.mockResolvedValueOnce({
            data: {
                data: [],
                meta: { current_page: 1, last_page: 1, per_page: 20, total: 0 },
                links: { first: null, last: null, prev: null, next: null },
            },
        });

        const { wrapper } = createWrapper();
        const filters = { status: 'pending' as const, contact_name: 'alice' };
        const { result } = renderHook(() => useScheduledMessages(filters), { wrapper });

        await waitFor(() => expect(result.current.isSuccess).toBe(true));

        expect(mockedApi.get).toHaveBeenCalledWith('/scheduled-messages', {
            params: filters,
        });
    });

    it('exposes isError true when API call fails', async () => {
        mockedApi.get.mockRejectedValueOnce(new Error('Network error'));
        const { wrapper } = createWrapper();
        const { result } = renderHook(() => useScheduledMessages(), { wrapper });

        await waitFor(() => expect(result.current.isError).toBe(true));
    });
});

describe('useCreateScheduledMessage()', () => {
    it('calls POST /scheduled-messages with payload', async () => {
        mockedApi.post.mockResolvedValueOnce({
            data: { message: 'Message scheduled successfully.', scheduled_message: mockMessage },
        });

        const { wrapper } = createWrapper();
        const { result } = renderHook(() => useCreateScheduledMessage(), { wrapper });

        const payload = {
            provider_connection_id: 1,
            contact_id: 'contact-123',
            contact_name: 'Alice Smith',
            message: 'Hello, Alice!',
            scheduled_at: '2026-03-01T10:00:00.000Z',
        };

        act(() => {
            result.current.mutate(payload);
        });

        await waitFor(() => expect(result.current.isSuccess).toBe(true));
        expect(mockedApi.post).toHaveBeenCalledWith('/scheduled-messages', payload);
        expect(result.current.data?.scheduled_message).toEqual(mockMessage);
    });

    it('invalidates scheduled-messages query on success', async () => {
        mockedApi.post.mockResolvedValueOnce({
            data: { message: 'OK', scheduled_message: mockMessage },
        });

        const { wrapper, queryClient } = createWrapper();
        const invalidateSpy = jest.spyOn(queryClient, 'invalidateQueries');
        const { result } = renderHook(() => useCreateScheduledMessage(), { wrapper });

        act(() => {
            result.current.mutate({
                provider_connection_id: 1,
                contact_id: 'c',
                contact_name: 'Name',
                message: 'Msg',
                scheduled_at: '2026-03-01T10:00:00.000Z',
            });
        });

        await waitFor(() => expect(result.current.isSuccess).toBe(true));
        expect(invalidateSpy).toHaveBeenCalledWith(
            expect.objectContaining({ queryKey: SCHEDULED_MESSAGES_QUERY_KEY }),
        );
    });

    it('exposes isError true when API call fails', async () => {
        mockedApi.post.mockRejectedValueOnce(new Error('Validation error'));
        const { wrapper } = createWrapper();
        const { result } = renderHook(() => useCreateScheduledMessage(), { wrapper });

        act(() => {
            result.current.mutate({
                provider_connection_id: 1,
                contact_id: 'c',
                contact_name: 'Name',
                message: 'Msg',
                scheduled_at: '2026-03-01T10:00:00.000Z',
            });
        });

        await waitFor(() => expect(result.current.isError).toBe(true));
    });
});

describe('useCancelScheduledMessage()', () => {
    it('calls DELETE /scheduled-messages/{id}', async () => {
        mockedApi.delete.mockResolvedValueOnce({
            data: { message: 'Scheduled message cancelled successfully.' },
        });

        const { wrapper } = createWrapper();
        const { result } = renderHook(() => useCancelScheduledMessage(), { wrapper });

        act(() => {
            result.current.mutate(1);
        });

        await waitFor(() => expect(result.current.isSuccess).toBe(true));
        expect(mockedApi.delete).toHaveBeenCalledWith('/scheduled-messages/1');
    });

    it('exposes isError true when deletion fails', async () => {
        mockedApi.delete.mockRejectedValueOnce(new Error('Forbidden'));
        const { wrapper } = createWrapper();
        const { result } = renderHook(() => useCancelScheduledMessage(), { wrapper });

        act(() => {
            result.current.mutate(1);
        });

        await waitFor(() => expect(result.current.isError).toBe(true));
    });
});

describe('useMessageLogs()', () => {
    it('fetches logs from GET /scheduled-messages/{id}/logs', async () => {
        mockedApi.get.mockResolvedValueOnce({
            data: { data: [mockLog] },
        });

        const { wrapper } = createWrapper();
        const { result } = renderHook(() => useMessageLogs(1), { wrapper });

        await waitFor(() => expect(result.current.isSuccess).toBe(true));
        expect(mockedApi.get).toHaveBeenCalledWith('/scheduled-messages/1/logs');
        expect(result.current.data?.data).toEqual([mockLog]);
    });

    it('does not fetch when id is null', () => {
        const { wrapper } = createWrapper();
        const { result } = renderHook(() => useMessageLogs(null), { wrapper });

        expect(result.current.fetchStatus).toBe('idle');
        expect(mockedApi.get).not.toHaveBeenCalled();
    });
});
