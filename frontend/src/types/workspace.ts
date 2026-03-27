export interface Workspace {
    id: number;
    name: string;
    role: "admin" | "operator" | null;
}