import React, { useState, useEffect } from 'react';
import { StatusBar } from 'expo-status-bar';
import { NavigationContainer, DefaultTheme } from '@react-navigation/native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { Ionicons } from '@expo/vector-icons';
import { ActivityIndicator, View } from 'react-native';

import LoginScreen from './src/screens/LoginScreen';
import HomeScreen from './src/screens/HomeScreen';
import LeaveScreen from './src/screens/LeaveScreen';
import PuantajScreen from './src/screens/PuantajScreen';
import ShiftScreen from './src/screens/ShiftScreen';
import MoreScreen from './src/screens/MoreScreen';
import ProfileScreen from './src/screens/ProfileScreen';
import api from './src/services/api';

const Tab = createBottomTabNavigator();

const DarkTheme = {
  ...DefaultTheme,
  colors: {
    ...DefaultTheme.colors,
    background: '#0F0A1E',
    card: '#1A1230',
    text: '#fff',
    border: '#2D2050',
    primary: '#7C3AED',
    notification: '#EF4444',
  },
};

export default function App() {
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [loading, setLoading] = useState(true);
  const [user, setUser] = useState<any>(null);
  const [firma, setFirma] = useState<any>(null);

  useEffect(() => {
    checkAuth();
  }, []);

  const checkAuth = async () => {
    await api.init();
    const token = await api.getToken();
    if (token) {
      try {
        const data: any = await api.profil();
        if (!data.hata) {
          setUser(data.personel);
          setFirma(data.firma);
          setIsLoggedIn(true);
        }
      } catch {
        await api.clearToken();
      }
    }
    setLoading(false);
  };

  const handleLogin = (data: any) => {
    setUser(data.personel);
    setFirma(data.firma);
    setIsLoggedIn(true);
  };

  const handleLogout = async () => {
    await api.cikisYap();
    setIsLoggedIn(false);
    setUser(null);
    setFirma(null);
  };

  if (loading) {
    return (
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#0F0A1E' }}>
        <ActivityIndicator size="large" color="#7C3AED" />
        <StatusBar style="light" />
      </View>
    );
  }

  if (!isLoggedIn) {
    return (
      <>
        <LoginScreen onLogin={handleLogin} />
        <StatusBar style="light" />
      </>
    );
  }

  return (
    <NavigationContainer theme={DarkTheme}>
      <Tab.Navigator
        screenOptions={({ route }) => ({
          headerShown: false,
          tabBarStyle: {
            backgroundColor: '#1A1230',
            borderTopColor: '#2D2050',
            borderTopWidth: 1,
            height: 70,
            paddingBottom: 10,
            paddingTop: 6,
          },
          tabBarActiveTintColor: '#A78BFA',
          tabBarInactiveTintColor: '#4B3F6B',
          tabBarLabelStyle: { fontSize: 10, fontWeight: '600' },
          tabBarIcon: ({ focused, color }) => {
            let iconName: any = 'home';
            if (route.name === 'Ana Sayfa') iconName = focused ? 'home' : 'home-outline';
            else if (route.name === 'İzinler') iconName = focused ? 'calendar' : 'calendar-outline';
            else if (route.name === 'Puantaj') iconName = focused ? 'stats-chart' : 'stats-chart-outline';
            else if (route.name === 'Vardiya') iconName = focused ? 'grid' : 'grid-outline';
            else if (route.name === 'Finans') iconName = focused ? 'wallet' : 'wallet-outline';
            else if (route.name === 'Profil') iconName = focused ? 'person' : 'person-outline';
            return <Ionicons name={iconName} size={20} color={color} />;
          },
        })}
      >
        <Tab.Screen name="Ana Sayfa">
          {() => <HomeScreen user={user} firma={firma} onLogout={handleLogout} />}
        </Tab.Screen>
        <Tab.Screen name="İzinler" component={LeaveScreen} />
        <Tab.Screen name="Puantaj" component={PuantajScreen} />
        <Tab.Screen name="Vardiya" component={ShiftScreen} />
        <Tab.Screen name="Finans">
          {() => <MoreScreen user={user} firma={firma} onLogout={handleLogout} />}
        </Tab.Screen>
        <Tab.Screen name="Profil">
          {() => <ProfileScreen user={user} firma={firma} onLogout={handleLogout} />}
        </Tab.Screen>
      </Tab.Navigator>
      <StatusBar style="light" />
    </NavigationContainer>
  );
}
