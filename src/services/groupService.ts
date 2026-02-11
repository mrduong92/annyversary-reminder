import api from "./api";
import type {
  AnniversaryGroup,
  GroupsResponse,
  GroupPreview,
  GroupSubscription,
  Subscriber,
} from "@/types";

export async function getGroups(): Promise<GroupsResponse> {
  const { data } = await api.get<GroupsResponse>("/groups");
  return data;
}

export async function createGroup(name: string): Promise<AnniversaryGroup> {
  const { data } = await api.post<AnniversaryGroup>("/groups", { name });
  return data;
}

export async function renameGroup(
  id: number,
  name: string
): Promise<AnniversaryGroup> {
  const { data } = await api.put<AnniversaryGroup>(`/groups/${id}`, { name });
  return data;
}

export async function deleteGroup(id: number): Promise<void> {
  await api.delete(`/groups/${id}`);
}

export async function shareGroup(
  id: number
): Promise<{ share_code: string }> {
  const { data } = await api.post<{ share_code: string }>(
    `/groups/${id}/share`
  );
  return data;
}

export async function revokeShare(id: number): Promise<void> {
  await api.delete(`/groups/${id}/share`);
}

export async function getSubscribers(id: number): Promise<Subscriber[]> {
  const { data } = await api.get<Subscriber[]>(`/groups/${id}/subscribers`);
  return data;
}

export async function removeSubscriber(
  groupId: number,
  userId: number
): Promise<void> {
  await api.delete(`/groups/${groupId}/subscribers/${userId}`);
}

export async function previewGroup(
  shareCode: string
): Promise<GroupPreview> {
  const { data } = await api.get<GroupPreview>("/groups/preview", {
    params: { share_code: shareCode },
  });
  return data;
}

export async function joinGroup(
  shareCode: string
): Promise<{ subscription: GroupSubscription; group: AnniversaryGroup }> {
  const { data } = await api.post<{
    subscription: GroupSubscription;
    group: AnniversaryGroup;
  }>("/groups/join", { share_code: shareCode });
  return data;
}

export async function unsubscribe(groupId: number): Promise<void> {
  await api.delete(`/groups/subscriptions/${groupId}`);
}
