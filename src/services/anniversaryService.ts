import api from "./api";
import type { Anniversary, AnniversaryInput } from "@/types";

export async function getAnniversaries(
  year?: number,
  groupId?: number
): Promise<Anniversary[]> {
  const params: Record<string, number> = {};
  if (year) params.year = year;
  if (groupId) params.group_id = groupId;
  const { data } = await api.get<Anniversary[]>("/anniversaries", { params });
  return data;
}

export async function createAnniversary(
  input: AnniversaryInput
): Promise<Anniversary> {
  const { data } = await api.post<Anniversary>("/anniversaries", input);
  return data;
}

export async function createBulkAnniversaries(
  items: AnniversaryInput[],
  groupId?: number
): Promise<Anniversary[]> {
  const { data } = await api.post<Anniversary[]>("/anniversaries/bulk", {
    items,
    group_id: groupId,
  });
  return data;
}

export async function updateAnniversary(
  id: number,
  input: Partial<AnniversaryInput>
): Promise<Anniversary> {
  const { data } = await api.put<Anniversary>(`/anniversaries/${id}`, input);
  return data;
}

export async function deleteAnniversary(id: number): Promise<void> {
  await api.delete(`/anniversaries/${id}`);
}
