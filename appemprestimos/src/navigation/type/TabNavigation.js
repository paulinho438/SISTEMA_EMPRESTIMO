import React, { useEffect, useState } from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import {
  View,
  ActivityIndicator,
  StyleSheet,
  Platform,
} from 'react-native';
import {
  BlackHome,
  SilverHome,
  BlackUser,
  SilverUser,
} from '../../assets/svgs';
import { TabNav } from '../navigationKeys';
import { TabRoute } from '../navigationRoute';
import { colors } from '../../themes/colors';
import { getTipoCliente } from '../../utils/asyncStorage';

const Tab = createBottomTabNavigator();

const TabNavigation = () => {
  const [tipo, setTipo] = useState(null);

  useEffect(() => {
    (async () => {
      const tipoUsuario = await getTipoCliente();
      setTipo(tipoUsuario);
    })();
  }, []);

  if (!tipo) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={colors.primary || '#000'} />
      </View>
    );
  }

  return (
    <Tab.Navigator>
      <Tab.Screen
        name={
          tipo === 'cliente'
            ? TabNav.HomeClienteScreen
            : TabNav.HomeScreen
        }
        component={
          tipo === 'cliente'
            ? TabRoute.HomeClienteScreen
            : TabRoute.HomeScreen
        }
        options={{
          headerShown: false,
          tabBarLabel: 'Home',
          tabBarActiveTintColor: colors.black,
          tabBarInactiveTintColor: colors.tabColor,
          tabBarIcon: ({ focused }) =>
            focused ? <BlackHome /> : <SilverHome />,
        }}
      />
      <Tab.Screen
        name={TabNav.ProfileScreen}
        component={TabRoute.ProfileScreen}
        options={{
          headerShown: false,
          tabBarLabel: 'Perfil',
          tabBarActiveTintColor: colors.black,
          tabBarInactiveTintColor: colors.tabColor,
          tabBarIcon: ({ focused }) =>
            focused ? <BlackUser /> : <SilverUser />,
        }}
      />
    </Tab.Navigator>
  );
};

const styles = StyleSheet.create({
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#fff',
  },
});

export default TabNavigation;
