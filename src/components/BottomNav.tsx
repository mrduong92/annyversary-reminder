import { useNavigate, useLocation } from "react-router-dom";
import { BottomNavigation, Icon } from "zmp-ui";

const tabs = [
  { path: "/", label: "Trang chủ", icon: "zi-home" },
  { path: "/list", label: "Danh sách", icon: "zi-list-1" },
  { path: "/groups", label: "Nhóm", icon: "zi-group" },
  { path: "/chat", label: "Chat AI", icon: "zi-chat" },
  { path: "/settings", label: "Cài đặt", icon: "zi-setting" },
];

export default function BottomNav() {
  const navigate = useNavigate();
  const location = useLocation();

  const activeTab = tabs.findIndex(
    (tab) =>
      tab.path === location.pathname ||
      (tab.path !== "/" && location.pathname.startsWith(tab.path))
  );

  return (
    <BottomNavigation
      fixed
      activeKey={activeTab >= 0 ? String(activeTab) : "0"}
      onChange={(key) => {
        navigate(tabs[Number(key)].path);
      }}
    >
      {tabs.map((tab, index) => (
        <BottomNavigation.Item
          key={index}
          label={tab.label}
          icon={<Icon icon={tab.icon as any} />}
          activeIcon={<Icon icon={tab.icon as any} />}
        />
      ))}
    </BottomNavigation>
  );
}
