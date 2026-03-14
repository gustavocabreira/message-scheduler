import '@testing-library/jest-dom';
import { TextEncoder, TextDecoder } from 'util';

// Mock Radix UI pointer capture APIs (not available in jsdom)
window.PointerEvent = MouseEvent as unknown as typeof PointerEvent;
Element.prototype.hasPointerCapture = jest.fn(() => false);
Element.prototype.setPointerCapture = jest.fn();
Element.prototype.releasePointerCapture = jest.fn();

// react-router-dom v7 requires TextEncoder/TextDecoder (not present in older jsdom)
Object.assign(global, { TextEncoder, TextDecoder });

// Mock window.matchMedia (not available in jsdom)
Object.defineProperty(window, 'matchMedia', {
    writable: true,
    value: jest.fn().mockImplementation((query: string) => ({
        matches: false,
        media: query,
        onchange: null,
        addListener: jest.fn(),
        removeListener: jest.fn(),
        addEventListener: jest.fn(),
        removeEventListener: jest.fn(),
        dispatchEvent: jest.fn(),
    })),
});

// Reset localStorage between tests so Zustand persist doesn't bleed state
beforeEach(() => {
    localStorage.clear();
});
