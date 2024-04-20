import React from 'react';

// Local imports
import {createNativeStackNavigator} from '@react-navigation/native-stack';
import {AuthNav, StackNav} from '../navigationKeys';
import {StackRoute} from '../navigationRoute';

const Stack = createNativeStackNavigator();

const StackNavigation = () => {
  return (
    <Stack.Navigator initialRouteName={AuthNav.splash}>
      <Stack.Screen
        name={StackNav.splash}
        component={StackRoute.Splash}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.OnBoarding}
        component={StackRoute.OnBoarding}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.AuthNavigation}
        component={StackRoute.AuthNavigation}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.TabNavigation}
        component={StackRoute.TabNavigation}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.TransferMoney}
        component={StackRoute.TransferMoney}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.Clientes}
        component={StackRoute.Clientes}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.SendMoney}
        component={StackRoute.SendMoney}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.TransferProof}
        component={StackRoute.TransferProof}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.TopUpScreen}
        component={StackRoute.TopUpScreen}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.Confirmation}
        component={StackRoute.Confirmation}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.WithDrawBalance}
        component={StackRoute.WithDrawBalance}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.HistoryTrans}
        component={StackRoute.HistoryTrans}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.HistoryDetails}
        component={StackRoute.HistoryDetails}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.SeeMyCard}
        component={StackRoute.SeeMyCard}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.EditCard}
        component={StackRoute.EditCard}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.AccountInfo}
        component={StackRoute.AccountInfo}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.EditAccount}
        component={StackRoute.EditAccount}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.SelectLanguage}
        component={StackRoute.SelectLanguage}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.GeneralSetting}
        component={StackRoute.GeneralSetting}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.ReferralCode}
        component={StackRoute.ReferralCode}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.ContactsList}
        component={StackRoute.ContactsList}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.Notification}
        component={StackRoute.Notification}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.FQA}
        component={StackRoute.FQA}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.ActivityGraph}
        component={StackRoute.ActivityGraph}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.MoreOptions}
        component={StackRoute.MoreOptions}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.ChatScreen}
        component={StackRoute.ChatScreen}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.ATMDetails}
        component={StackRoute.ATMDetails}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.CobrancaMap}
        component={StackRoute.CobrancaMap}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.SelectProvider}
        component={StackRoute.SelectProvider}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.TopUpModal}
        component={StackRoute.TopUpModal}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.PhoneBook}
        component={StackRoute.PhoneBook}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name={StackNav.LogOut}
        component={StackRoute.LogOut}
        options={{headerShown: false}}
      />
    </Stack.Navigator>
  );
};

export default StackNavigation;
