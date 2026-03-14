export type ScheduledMessageStatus = 'pending' | 'processing' | 'sent' | 'failed' | 'cancelled';

export interface ScheduledMessage {
    id: number;
    contact_id: string;
    contact_name: string;
    message: string;
    scheduled_at: string;
    status: ScheduledMessageStatus;
    attempts: number;
    provider_connection_id: number;
    created_at: string;
    updated_at: string;
    provider_connection: {
        id: number;
        provider_type: string;
        provider_label: string;
    } | null;
}

export interface MessageLog {
    id: number;
    attempt: number;
    status: string;
    response: string | null;
    error_message: string | null;
    created_at: string;
}

export interface ScheduledMessagesListResponse {
    data: ScheduledMessage[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
}

export interface ScheduledMessageResponse {
    message: string;
    scheduled_message: ScheduledMessage;
}

export interface MessageLogsResponse {
    data: MessageLog[];
}

export interface CreateScheduledMessagePayload {
    provider_connection_id: number;
    contact_id: string;
    contact_name: string;
    message: string;
    scheduled_at: string;
}

export interface ScheduledMessagesFilters {
    status?: ScheduledMessageStatus;
    contact_name?: string;
    page?: number;
}
