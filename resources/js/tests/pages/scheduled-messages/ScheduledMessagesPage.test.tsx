import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ScheduledMessagesPage } from '@/pages/scheduled-messages/ScheduledMessagesPage';
import { renderWithProviders } from '../../helpers/renderWithProviders';
import type { ScheduledMessage } from '@/types/scheduled-message';

jest.mock('@/lib/api', () => ({
    __esModule: true,
    default: { get: jest.fn(), post: jest.fn(), delete: jest.fn() },
}));

jest.mock('sonner', () => ({
    toast: { success: jest.fn(), error: jest.fn() },
}));

import api from '@/lib/api';
const mockedApi = api as jest.Mocked<typeof api>;

const mockMessage: ScheduledMessage = {
    id: 1,
    contact_id: 'c-1',
    contact_name: 'Alice Smith',
    message: 'Hello, Alice! This is a scheduled message.',
    scheduled_at: '2026-03-01T10:00:00Z',
    status: 'pending',
    attempts: 0,
    provider_connection_id: 1,
    created_at: '2026-02-18T21:00:00Z',
    updated_at: '2026-02-18T21:00:00Z',
    provider_connection: { id: 1, provider_type: 'huggy', provider_label: 'Huggy' },
};

const sentMessage: ScheduledMessage = {
    ...mockMessage,
    id: 2,
    contact_name: 'Bob Jones',
    status: 'sent',
    attempts: 1,
};

function mockListResponse(messages: ScheduledMessage[], total = messages.length) {
    return {
        data: {
            data: messages,
            meta: { current_page: 1, last_page: 1, per_page: 20, total },
            links: { first: null, last: null, prev: null, next: null },
        },
    };
}

beforeEach(() => {
    jest.clearAllMocks();
});

describe('ScheduledMessagesPage — rendering', () => {
    it('renders the page heading', () => {
        mockedApi.get.mockReturnValueOnce(new Promise(() => {}) as never);
        renderWithProviders(<ScheduledMessagesPage />);
        expect(screen.getByRole('heading', { name: /agendamentos/i })).toBeInTheDocument();
    });

    it('renders loading skeletons while fetching', () => {
        mockedApi.get.mockReturnValueOnce(new Promise(() => {}) as never);
        renderWithProviders(<ScheduledMessagesPage />);
        const skeletons = document.querySelectorAll('[class*="animate-pulse"]');
        expect(skeletons.length).toBeGreaterThan(0);
    });

    it('shows empty state when no messages exist', async () => {
        mockedApi.get.mockResolvedValueOnce(mockListResponse([]));
        renderWithProviders(<ScheduledMessagesPage />);

        await waitFor(() => {
            expect(
                screen.getByText(/nenhum agendamento encontrado/i),
            ).toBeInTheDocument();
        });
    });

    it('renders message cards when messages exist', async () => {
        mockedApi.get.mockResolvedValueOnce(mockListResponse([mockMessage]));
        renderWithProviders(<ScheduledMessagesPage />);

        await waitFor(() => {
            expect(screen.getByText('Alice Smith')).toBeInTheDocument();
        });
    });

    it('renders multiple message cards', async () => {
        mockedApi.get.mockResolvedValueOnce(mockListResponse([mockMessage, sentMessage]));
        renderWithProviders(<ScheduledMessagesPage />);

        await waitFor(() => {
            expect(screen.getByText('Alice Smith')).toBeInTheDocument();
            expect(screen.getByText('Bob Jones')).toBeInTheDocument();
        });
    });

    it('shows error banner when API fails', async () => {
        mockedApi.get.mockRejectedValueOnce(new Error('Network error'));
        renderWithProviders(<ScheduledMessagesPage />);

        await waitFor(() => {
            expect(
                screen.getByText(/erro ao carregar os agendamentos/i),
            ).toBeInTheDocument();
        });
    });

    it('shows pagination when there are multiple pages', async () => {
        mockedApi.get.mockResolvedValueOnce({
            data: {
                data: [mockMessage],
                meta: { current_page: 1, last_page: 3, per_page: 20, total: 60 },
                links: { first: null, last: null, prev: null, next: null },
            },
        });
        renderWithProviders(<ScheduledMessagesPage />);

        await waitFor(() => {
            expect(screen.getByText(/página 1 de 3/i)).toBeInTheDocument();
        });
    });
});

describe('ScheduledMessagesPage — create dialog', () => {
    it('opens create dialog when "Novo agendamento" button is clicked in empty state', async () => {
        mockedApi.get
            .mockResolvedValueOnce(mockListResponse([]))
            .mockResolvedValueOnce({ data: { data: [] } }); // useProviders when dialog mounts
        const user = userEvent.setup();
        renderWithProviders(<ScheduledMessagesPage />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /novo agendamento/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /novo agendamento/i }));

        expect(screen.getByRole('dialog')).toBeInTheDocument();
        expect(screen.getByText(/novo agendamento/i, { selector: '[role="heading"], h2' })).toBeInTheDocument();
    });

    it('opens create dialog when header button is clicked (messages exist)', async () => {
        mockedApi.get
            .mockResolvedValueOnce(mockListResponse([mockMessage]))
            .mockResolvedValueOnce({ data: { data: [] } }); // useProviders when dialog mounts
        const user = userEvent.setup();
        renderWithProviders(<ScheduledMessagesPage />);

        await waitFor(() => {
            expect(screen.getByText('Alice Smith')).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /novo agendamento/i }));
        expect(screen.getByRole('dialog')).toBeInTheDocument();
    });
});

describe('ScheduledMessagesPage — status filter', () => {
    it('renders status filter buttons', async () => {
        mockedApi.get.mockResolvedValueOnce(mockListResponse([]));
        renderWithProviders(<ScheduledMessagesPage />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /^todos$/i })).toBeInTheDocument();
            expect(screen.getByRole('button', { name: /pendentes/i })).toBeInTheDocument();
            expect(screen.getByRole('button', { name: /enviados/i })).toBeInTheDocument();
        });
    });

    it('clicking a status filter re-queries the API with correct params', async () => {
        mockedApi.get.mockResolvedValue(mockListResponse([mockMessage]));

        const user = userEvent.setup();
        renderWithProviders(<ScheduledMessagesPage />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /enviados/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /enviados/i }));

        await waitFor(() => {
            expect(mockedApi.get).toHaveBeenCalledWith('/scheduled-messages', {
                params: expect.objectContaining({ status: 'sent' }),
            });
        });
    });
});

describe('ScheduledMessagesPage — contact name filter', () => {
    it('renders the contact name search input', () => {
        mockedApi.get.mockReturnValueOnce(new Promise(() => {}) as never);
        renderWithProviders(<ScheduledMessagesPage />);
        expect(screen.getByPlaceholderText(/buscar por contato/i)).toBeInTheDocument();
    });

    it('typing in contact name search updates the filter', async () => {
        mockedApi.get.mockResolvedValue(mockListResponse([mockMessage]));

        const user = userEvent.setup();
        renderWithProviders(<ScheduledMessagesPage />);

        await waitFor(() => {
            expect(screen.getByPlaceholderText(/buscar por contato/i)).toBeInTheDocument();
        });

        await user.type(screen.getByPlaceholderText(/buscar por contato/i), 'alice');

        await waitFor(() => {
            expect(mockedApi.get).toHaveBeenCalledWith('/scheduled-messages', {
                params: expect.objectContaining({ contact_name: 'alice' }),
            });
        });
    });
});
