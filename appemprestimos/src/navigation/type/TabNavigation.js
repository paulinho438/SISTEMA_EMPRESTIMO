import React from 'react';
import {createBottomTabNavigator} from '@react-navigation/bottom-tabs';
import {TabNav} from '../navigationKeys';
import {TabRoute} from '../navigationRoute';
import {
  BlackActivity,
  BlackCreditCard,
  BlackHome,
  BlackUser,
  Scan,
  SilverActivity,
  SilverCreditCard,
  SilverHome,
  SilverUser,
} from '../../assets/svgs';
import {colors} from '../../themes/colors';
import {StyleSheet} from 'react-native';
import {moderateScale} from '../../common/constant';

const Tab = createBottomTabNavigator();

const TabNavigation = () => {
  return (
    <Tab.Navigator>
      <Tab.Screen
        name={TabNav.HomeScreen}
        component={TabRoute.HomeScreen}
        options={{
          headerShown: false,
          tabBarLabel: 'Home',
          tabBarActiveTintColor: colors.black,
          tabBarInactiveTintColor: colors.tabColor,
          tabBarIcon: ({focused}) => (focused ? <BlackHome /> : <SilverHome />),
        }}
      />
      <Tab.Screen
        name={TabNav.MyCardScreen}
        component={TabRoute.MyCardScreen}
        options={{
          headerShown: false,
          tabBarLabel: 'Emprestimo',
          tabBarActiveTintColor: colors.black,
          tabBarInactiveTintColor: colors.tabColor,
          tabBarIcon: ({focused}) =>
            focused ? <BlackCreditCard /> : <SilverCreditCard />,
        }}
      />
      <Tab.Screen
        name={TabNav.ActivityScreen}
        component={TabRoute.ActivityScreen}
        options={{
          headerShown: false,
          tabBarLabel: 'Log',
          tabBarActiveTintColor: colors.black,
          tabBarInactiveTintColor: colors.tabColor,
          tabBarIcon: ({focused}) =>
            focused ? <BlackActivity /> : <SilverActivity />,
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
          tabBarIcon: ({focused}) => (focused ? <BlackUser /> : <SilverUser />),
        }}
      />
    </Tab.Navigator>
  );
};

const localStyles = StyleSheet.create({
  ScanImg: {
    top: moderateScale(7),
  },
});

export default TabNavigation;
