import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { TooltipProvider } from '@/components/ui/tooltip';
import { Toaster } from '@/components/ui/sonner';
import { ThemeProvider } from '@/components/shared/ThemeProvider';
import { AppRouter } from '@/router';
import '../css/app.css';

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            refetchOnWindowFocus: false,
            retry: 1,
        },
    },
});

const rootElement = document.getElementById('root');

if (!rootElement) {
    throw new Error('Root element not found. Make sure <div id="root"> exists in your HTML.');
}

createRoot(rootElement).render(
    <StrictMode>
        <QueryClientProvider client={queryClient}>
            <ThemeProvider>
                <TooltipProvider>
                    <AppRouter />
                    <Toaster richColors position="top-right" />
                </TooltipProvider>
            </ThemeProvider>
        </QueryClientProvider>
    </StrictMode>,
);
