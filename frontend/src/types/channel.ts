export interface Channel {
    id: number;
    name: string;
    slug: string;
}

export interface Entrypoint {
    id: number
    name: string
    type: string
    uuid: string
    provider: string
    entrypoint: string
}