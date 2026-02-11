import { useEffect } from "react";
import { useAtom } from "jotai";
import { getSystemInfo } from "zmp-sdk";
import {
  AnimationRoutes,
  App,
  Route,
  SnackbarProvider,
  ZMPRouter,
} from "zmp-ui";
import { AppProps } from "zmp-ui/app";

import { userAtom, tokenAtom } from "@/store/atoms";
import { login, isLoggedIn } from "@/services/auth";

import HomePage from "@/pages/index";
import AnniversaryListPage from "@/pages/list";
import AddAnniversaryPage from "@/pages/add";
import EditAnniversaryPage from "@/pages/edit";
import OcrUploadPage from "@/pages/ocr";
import ChatPage from "@/pages/chat";
import SettingsPage from "@/pages/settings";
import GroupsPage from "@/pages/groups";
import GroupDetailPage from "@/pages/group-detail";
import JoinPage from "@/pages/join";

const Layout = () => {
  const [, setUser] = useAtom(userAtom);
  const [, setToken] = useAtom(tokenAtom);

  useEffect(() => {
    initAuth();
    detectDeepLink();
  }, []);

  const initAuth = async () => {
    try {
      if (!isLoggedIn()) {
        const { token, user } = await login();
        setToken(token);
        setUser(user);
      }
    } catch (err) {
      console.error("Auth failed:", err);
    }
  };

  const detectDeepLink = () => {
    const params = new URLSearchParams(window.location.search);
    const shareCode = params.get("share_code");
    if (shareCode) {
      window.location.hash = `#/join?code=${shareCode}`;
    }
  };

  return (
    <App theme={getSystemInfo().zaloTheme as AppProps["theme"]}>
      <SnackbarProvider>
        <ZMPRouter>
          <AnimationRoutes>
            <Route path="/" element={<HomePage />} />
            <Route path="/list" element={<AnniversaryListPage />} />
            <Route path="/add" element={<AddAnniversaryPage />} />
            <Route path="/edit/:id" element={<EditAnniversaryPage />} />
            <Route path="/ocr" element={<OcrUploadPage />} />
            <Route path="/chat" element={<ChatPage />} />
            <Route path="/settings" element={<SettingsPage />} />
            <Route path="/groups" element={<GroupsPage />} />
            <Route path="/group/:id" element={<GroupDetailPage />} />
            <Route path="/join" element={<JoinPage />} />
          </AnimationRoutes>
        </ZMPRouter>
      </SnackbarProvider>
    </App>
  );
};

export default Layout;
