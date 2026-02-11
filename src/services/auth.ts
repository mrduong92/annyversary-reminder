import { getAccessToken } from "zmp-sdk";
import api from "./api";
import type { User } from "@/types";

interface LoginResponse {
  token: string;
  user: User;
}

export async function login(): Promise<LoginResponse> {
  const accessToken = await getAccessToken({});
  const { data } = await api.post<LoginResponse>("/auth/login", {
    access_token: accessToken,
  });

  localStorage.setItem("jwt_token", data.token);
  return data;
}

export function logout() {
  localStorage.removeItem("jwt_token");
}

export function isLoggedIn(): boolean {
  return !!localStorage.getItem("jwt_token");
}
