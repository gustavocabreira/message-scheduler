export type ProviderType = 'huggy';

export type ProviderStatus = 'active' | 'inactive' | 'error';

export interface ProviderConnection {
    id: number;
    provider_type: ProviderType;
    provider_label: string;
    status: ProviderStatus;
    settings: Record<string, unknown> | null;
    connected_at: string | null;
    last_synced_at: string | null;
    created_at: string;
}

export interface Contact {
    id: string;
    name: string;
    phone: string | null;
    email: string | null;
}

export interface ProvidersListResponse {
    data: ProviderConnection[];
}

export interface ProviderResponse {
    provider: ProviderConnection;
}

export interface TestConnectionResponse {
    connected: boolean;
    provider: ProviderConnection;
}

export interface HuggyRedirectResponse {
    authorization_url: string;
}

export interface ContactsResponse {
    data: Contact[];
}
