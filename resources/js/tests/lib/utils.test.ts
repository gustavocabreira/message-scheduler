import { cn } from '@/lib/utils';

describe('cn()', () => {
    it('returns a single class unchanged', () => {
        expect(cn('foo')).toBe('foo');
    });

    it('merges multiple classes', () => {
        expect(cn('foo', 'bar')).toBe('foo bar');
    });

    it('ignores falsy values', () => {
        expect(cn('foo', false, undefined, null, '')).toBe('foo');
    });

    it('deduplicates conflicting Tailwind classes (last one wins)', () => {
        expect(cn('p-2', 'p-4')).toBe('p-4');
    });

    it('deduplicates conflicting text color classes', () => {
        expect(cn('text-red-500', 'text-blue-500')).toBe('text-blue-500');
    });

    it('merges conditional classes correctly', () => {
        const isActive = true;
        expect(cn('base', isActive && 'active')).toBe('base active');
    });

    it('handles object syntax from clsx', () => {
        expect(cn({ foo: true, bar: false })).toBe('foo');
    });

    it('handles array syntax from clsx', () => {
        expect(cn(['foo', 'bar'])).toBe('foo bar');
    });

    it('returns empty string when no arguments', () => {
        expect(cn()).toBe('');
    });
});
