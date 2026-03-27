import type { Channel } from "@/types/channel";
import { http } from "./http";

export const channelService = {
    async getChannels() {
        return await http.request<Channel[]>("GET", "/v1/channels?status=active");
    },
}