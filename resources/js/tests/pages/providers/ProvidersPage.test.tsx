import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ProvidersPage } from '@/pages/providers/ProvidersPage';
import { renderWithProviders } from '../../helpers/renderWithProviders';
import type { ProviderConnection } from '@/types/provider';

jest.mock('@/lib/api', () => ({
    __esModule: true,
    default: { get: jest.fn(), post: jest.fn(), delete: jest.fn() },
}));

jest.mock('@/lib/navigate', () => ({
    navigateTo: jest.fn(),
}));

jest.mock('sonner', () => ({
    toast: { success: jest.fn(), warning: jest.fn(), error: jest.fn() },
}));

import api from '@/lib/api';
import { navigateTo } from '@/lib/navigate';
const mockedApi = api as jest.Mocked<typeof api>;
const mockedNavigateTo = navigateTo as jest.Mock;

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

beforeEach(() => {
    jest.clearAllMocks();
});

describe('ProvidersPage — rendering', () => {
    it('renders the page heading', async () => {
        mockedApi.get.mockResolvedValueOnce({ data: { data: [] } });
        renderWithProviders(<ProvidersPage />);
        expect(screen.getByRole('heading', { name: /providers/i })).toBeInTheDocument();
    });

    it('renders loading skeletons while fetching', () => {
        let resolve: (v: unknown) => void;
        mockedApi.get.mockReturnValueOnce(new Promise((res) => { resolve = res; }) as never);
        renderWithProviders(<ProvidersPage />);
        // Skeletons are rendered as divs with animate-pulse class
        const skeletons = document.querySelectorAll('[class*="animate-pulse"]');
        expect(skeletons.length).toBeGreaterThan(0);
        resolve!({ data: { data: [] } });
    });

    it('shows empty state when no providers are connected', async () => {
        mockedApi.get.mockResolvedValueOnce({ data: { data: [] } });
        renderWithProviders(<ProvidersPage />);

        await waitFor(() => {
            expect(screen.getByText(/nenhum provider conectado/i)).toBeInTheDocument();
        });
    });

    it('shows connect button in empty state', async () => {
        mockedApi.get.mockResolvedValueOnce({ data: { data: [] } });
        renderWithProviders(<ProvidersPage />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /conectar huggy/i })).toBeInTheDocument();
        });
    });

    it('renders provider cards when providers exist', async () => {
        mockedApi.get.mockResolvedValueOnce({ data: { data: [mockProvider] } });
        renderWithProviders(<ProvidersPage />);

        await waitFor(() => {
            expect(screen.getByText('Huggy')).toBeInTheDocument();
        });
    });

    it('renders a connect button in the header when providers exist', async () => {
        mockedApi.get.mockResolvedValueOnce({ data: { data: [mockProvider] } });
        renderWithProviders(<ProvidersPage />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /conectar huggy/i })).toBeInTheDocument();
        });
    });

    it('renders an error banner when the API fails', async () => {
        mockedApi.get.mockRejectedValueOnce(new Error('Network error'));
        renderWithProviders(<ProvidersPage />);

        await waitFor(() => {
            expect(screen.getByText(/erro ao carregar os providers/i)).toBeInTheDocument();
        });
    });

    it('renders multiple provider cards when multiple providers exist', async () => {
        const secondProvider: ProviderConnection = { ...mockProvider, id: 2 };
        mockedApi.get.mockResolvedValueOnce({ data: { data: [mockProvider, secondProvider] } });
        renderWithProviders(<ProvidersPage />);

        await waitFor(() => {
            expect(screen.getAllByText('Huggy').length).toBeGreaterThanOrEqual(2);
        });
    });
});

describe('ProvidersPage — connect Huggy flow', () => {
    it('initiates OAuth redirect when "Conectar Huggy" is clicked in empty state', async () => {
        const authUrl = 'https://huggy.io/oauth/authorize?client_id=test';
        mockedApi.get
            .mockResolvedValueOnce({ data: { data: [] } })
            .mockResolvedValueOnce({ data: { authorization_url: authUrl } });

        const user = userEvent.setup();
        renderWithProviders(<ProvidersPage />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /conectar huggy/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /conectar huggy/i }));

        await waitFor(() => {
            expect(mockedNavigateTo).toHaveBeenCalledWith(authUrl);
        });
    });
});
