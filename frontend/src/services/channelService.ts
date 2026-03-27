import type { Channel, Entrypoint } from "@/types/channel";
import { http } from "./http";

export const channelService = {
    async getChannels() {
        return await http.request<Channel[]>("GET", "/v1/channels?status=active");
    },
    async getChannelEntrypoints(channelId: number) {
        return await http.request<Entrypoint[]>("GET", `/v1/channels/${channelId}/entrypoints`);
    },
}