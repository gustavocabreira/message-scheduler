import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/api';
import type {
    ScheduledMessagesListResponse,
    ScheduledMessageResponse,
    MessageLogsResponse,
    CreateScheduledMessagePayload,
    ScheduledMessagesFilters,
} from '@/types/scheduled-message';

export const SCHEDULED_MESSAGES_QUERY_KEY = ['scheduled-messages'] as const;

export function useScheduledMessages(filters?: ScheduledMessagesFilters) {
    return useQuery<ScheduledMessagesListResponse>({
        queryKey: [...SCHEDULED_MESSAGES_QUERY_KEY, filters],
        queryFn: () =>
            api
                .get<ScheduledMessagesListResponse>('/scheduled-messages', { params: filters })
                .then((r) => r.data),
    });
}

export function useCreateScheduledMessage() {
    const queryClient = useQueryClient();

    return useMutation<ScheduledMessageResponse, Error, CreateScheduledMessagePayload>({
        mutationFn: (payload) =>
            api
                .post<ScheduledMessageResponse>('/scheduled-messages', payload)
                .then((r) => r.data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: SCHEDULED_MESSAGES_QUERY_KEY });
        },
    });
}

export function useCancelScheduledMessage() {
    const queryClient = useQueryClient();

    return useMutation<void, Error, number>({
        mutationFn: (id) =>
            api.delete(`/scheduled-messages/${id}`).then(() => undefined),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: SCHEDULED_MESSAGES_QUERY_KEY });
        },
    });
}

export function useMessageLogs(id: number | null) {
    return useQuery<MessageLogsResponse>({
        queryKey: [...SCHEDULED_MESSAGES_QUERY_KEY, id, 'logs'],
        queryFn: () =>
            api
                .get<MessageLogsResponse>(`/scheduled-messages/${id}/logs`)
                .then((r) => r.data),
        enabled: id !== null,
    });
}
