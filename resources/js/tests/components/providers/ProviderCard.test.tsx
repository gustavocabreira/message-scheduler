import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ProviderCard } from '@/components/providers/ProviderCard';
import { renderWithProviders } from '../../helpers/renderWithProviders';
import type { ProviderConnection } from '@/types/provider';

jest.mock('@/lib/api', () => ({
    __esModule: true,
    default: { get: jest.fn(), post: jest.fn(), delete: jest.fn() },
}));

// Mock sonner toast so we can assert on it
jest.mock('sonner', () => ({
    toast: {
        success: jest.fn(),
        warning: jest.fn(),
        error: jest.fn(),
    },
}));

import api from '@/lib/api';
import { toast } from 'sonner';

const mockedApi = api as jest.Mocked<typeof api>;
const mockedToast = toast as jest.Mocked<typeof toast>;

const activeProvider: ProviderConnection = {
    id: 1,
    provider_type: 'huggy',
    provider_label: 'Huggy',
    status: 'active',
    settings: null,
    connected_at: '2026-02-18T21:00:00Z',
    last_synced_at: null,
    created_at: '2026-02-18T21:00:00Z',
};

const errorProvider: ProviderConnection = { ...activeProvider, status: 'error' };
const inactiveProvider: ProviderConnection = { ...activeProvider, status: 'inactive' };

beforeEach(() => {
    jest.clearAllMocks();
});

describe('ProviderCard — rendering', () => {
    it('renders the provider label', () => {
        renderWithProviders(<ProviderCard provider={activeProvider} />);
        expect(screen.getByText('Huggy')).toBeInTheDocument();
    });

    it('renders the provider type', () => {
        renderWithProviders(<ProviderCard provider={activeProvider} />);
        expect(screen.getByText('huggy')).toBeInTheDocument();
    });

    it('shows "Ativo" badge for active status', () => {
        renderWithProviders(<ProviderCard provider={activeProvider} />);
        expect(screen.getByText('Ativo')).toBeInTheDocument();
    });

    it('shows "Erro" badge for error status', () => {
        renderWithProviders(<ProviderCard provider={errorProvider} />);
        expect(screen.getByText('Erro')).toBeInTheDocument();
    });

    it('shows "Inativo" badge for inactive status', () => {
        renderWithProviders(<ProviderCard provider={inactiveProvider} />);
        expect(screen.getByText('Inativo')).toBeInTheDocument();
    });

    it('renders the test connection button', () => {
        renderWithProviders(<ProviderCard provider={activeProvider} />);
        expect(screen.getByRole('button', { name: /testar conexão/i })).toBeInTheDocument();
    });

    it('renders the delete button', () => {
        renderWithProviders(<ProviderCard provider={activeProvider} />);
        expect(screen.getByRole('button', { name: /remover conexão/i })).toBeInTheDocument();
    });

    it('shows "—" for null connected_at', () => {
        renderWithProviders(<ProviderCard provider={{ ...activeProvider, connected_at: null }} />);
        expect(screen.getAllByText('—').length).toBeGreaterThan(0);
    });
});

describe('ProviderCard — test connection', () => {
    it('calls POST /providers/{id}/test-connection when button is clicked', async () => {
        mockedApi.post.mockResolvedValueOnce({
            data: { connected: true, provider: activeProvider },
        });
        const user = userEvent.setup();
        renderWithProviders(<ProviderCard provider={activeProvider} />);

        await user.click(screen.getByRole('button', { name: /testar conexão/i }));

        await waitFor(() => {
            expect(mockedApi.post).toHaveBeenCalledWith('/providers/1/test-connection');
        });
    });

    it('shows success toast when connection is ok', async () => {
        mockedApi.post.mockResolvedValueOnce({
            data: { connected: true, provider: activeProvider },
        });
        const user = userEvent.setup();
        renderWithProviders(<ProviderCard provider={activeProvider} />);

        await user.click(screen.getByRole('button', { name: /testar conexão/i }));

        await waitFor(() => {
            expect(mockedToast.success).toHaveBeenCalled();
        });
    });

    it('shows warning toast when connection fails', async () => {
        mockedApi.post.mockResolvedValueOnce({
            data: { connected: false, provider: errorProvider },
        });
        const user = userEvent.setup();
        renderWithProviders(<ProviderCard provider={activeProvider} />);

        await user.click(screen.getByRole('button', { name: /testar conexão/i }));

        await waitFor(() => {
            expect(mockedToast.warning).toHaveBeenCalled();
        });
    });

    it('shows error toast when API call itself fails', async () => {
        mockedApi.post.mockRejectedValueOnce(new Error('Network error'));
        const user = userEvent.setup();
        renderWithProviders(<ProviderCard provider={activeProvider} />);

        await user.click(screen.getByRole('button', { name: /testar conexão/i }));

        await waitFor(() => {
            expect(mockedToast.error).toHaveBeenCalled();
        });
    });
});

describe('ProviderCard — delete', () => {
    it('opens the confirmation dialog when delete button is clicked', async () => {
        const user = userEvent.setup();
        renderWithProviders(<ProviderCard provider={activeProvider} />);

        await user.click(screen.getByRole('button', { name: /remover conexão/i }));

        expect(screen.getByRole('alertdialog')).toBeInTheDocument();
        expect(screen.getByText(/remover conexão/i)).toBeInTheDocument();
    });

    it('mentions the provider name in the confirmation dialog', async () => {
        const user = userEvent.setup();
        renderWithProviders(<ProviderCard provider={activeProvider} />);

        await user.click(screen.getByRole('button', { name: /remover conexão/i }));

        expect(screen.getByText(/huggy/i, { selector: 'strong' })).toBeInTheDocument();
    });

    it('closes the dialog when "Cancelar" is clicked', async () => {
        const user = userEvent.setup();
        renderWithProviders(<ProviderCard provider={activeProvider} />);

        await user.click(screen.getByRole('button', { name: /remover conexão/i }));
        await user.click(screen.getByRole('button', { name: /cancelar/i }));

        await waitFor(() => {
            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
        });
    });

    it('calls DELETE /providers/{id} when "Remover" is confirmed', async () => {
        mockedApi.delete.mockResolvedValueOnce({ data: { message: 'Deleted.' } });
        const user = userEvent.setup();
        renderWithProviders(<ProviderCard provider={activeProvider} />);

        await user.click(screen.getByRole('button', { name: /remover conexão/i }));
        await user.click(screen.getByRole('button', { name: /^remover$/i }));

        await waitFor(() => {
            expect(mockedApi.delete).toHaveBeenCalledWith('/providers/1');
        });
    });

    it('shows success toast after successful deletion', async () => {
        mockedApi.delete.mockResolvedValueOnce({ data: { message: 'Deleted.' } });
        const user = userEvent.setup();
        renderWithProviders(<ProviderCard provider={activeProvider} />);

        await user.click(screen.getByRole('button', { name: /remover conexão/i }));
        await user.click(screen.getByRole('button', { name: /^remover$/i }));

        await waitFor(() => {
            expect(mockedToast.success).toHaveBeenCalled();
        });
    });

    it('shows error toast when deletion fails', async () => {
        mockedApi.delete.mockRejectedValueOnce(new Error('Server error'));
        const user = userEvent.setup();
        renderWithProviders(<ProviderCard provider={activeProvider} />);

        await user.click(screen.getByRole('button', { name: /remover conexão/i }));
        await user.click(screen.getByRole('button', { name: /^remover$/i }));

        await waitFor(() => {
            expect(mockedToast.error).toHaveBeenCalled();
        });
    });
});
