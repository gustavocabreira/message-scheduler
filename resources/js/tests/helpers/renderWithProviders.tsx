import { type ReactNode } from 'react';
import { render, type RenderOptions } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter, type MemoryRouterProps } from 'react-router-dom';

interface RenderWithProvidersOptions extends Omit<RenderOptions, 'wrapper'> {
    routerProps?: MemoryRouterProps;
}

export function createTestQueryClient() {
    return new QueryClient({
        defaultOptions: {
            queries: { retry: false, gcTime: 0 },
            mutations: { retry: false },
        },
    });
}

export function renderWithProviders(
    ui: ReactNode,
    { routerProps, ...renderOptions }: RenderWithProvidersOptions = {},
) {
    const queryClient = createTestQueryClient();

    function Wrapper({ children }: { children: ReactNode }) {
        return (
            <QueryClientProvider client={queryClient}>
                <MemoryRouter {...routerProps}>{children}</MemoryRouter>
            </QueryClientProvider>
        );
    }

    return { queryClient, ...render(ui, { wrapper: Wrapper, ...renderOptions }) };
}
