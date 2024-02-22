import React from 'react'
import { NavigationContainer } from '@react-navigation/native'
import StackNavigation from './type/StackNavigation'

export default function AppNavigator() {
  return (
    <NavigationContainer>
      <StackNavigation />
    </NavigationContainer>
  )
}